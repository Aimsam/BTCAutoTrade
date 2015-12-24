<?php

/**
 * Class AccountGroupModel
 * @author Tuzki
 * @desc 账号组Model 对应 account_group 表，通过一组账号定义一个对冲组
 */
class AccountGroupModel {

    private $id;
    private $holding_btc_weight_total = 0;
    public $name;
    public $config;
    public $accounts = array();

    public function __construct($id) {
        $this->id = $id;
    }

    /**
     * @author Tuzki
     * @param integer
     * @desc 将改组账号的配置设置到对象的属性中，new 出账号组的账号对象
     */
    private function init() {
        Logger::Log("Initialize account group from db.");
        $this->accounts = array();
        $this->config = null;
        $this->name = null;
        $this->holding_btc_weight_total = 0;
        $account_dao = new AccountDao();
        $account_group_dao = new AccountGroupDao();
        $account_group = $account_group_dao->get($this->id);
        if($account_group) {
            $this->name = $account_group['name'];
        } else {
            throw new Exception('Account Group is not found.');
        }

        $this->id = $account_group['id'];
        $this->config = new ConfigModel($this->id);
        $accounts = $account_dao->getAccountListByAccountGroupId($this->id);
        foreach($accounts as $account) {
            $account = new AccountModel($account['id']);
            //持币权重之和（后面用于计算交易类型）
            $this->holding_btc_weight_total += $account->market->holding_btc_weight;
            $this->accounts[] = $account;
        }
    }

    public function availableTrades($force_update_info = false) {
        $this->init();
        $this->matchInfo($force_update_info);
        $this->matchPriceActions();
        $this->matchPercentage();
        return $this->generateTrade();
    }

    /**
     * @desc 更新账号组所有账号价格 第一步
     */
    private function matchPriceActions() {
        foreach($this->accounts as $key => $account) {
            if($account->matchPriceActions($this->config->trans_unit) == false) {
                // 如果匹配价格失败（size 不够或者延迟太久）从 accounts 中移除
                unset($this->accounts[$key]);
            }
        }
        // 如果价格不延迟的市场少于两个不能对冲，重新获取所有价格
        if(count($this->accounts) < 2) {
            Logger::Log("Only less than 2 market price matched, sleep 1 second, try again.");
            sleep(1);
            $this->init();
            return $this->matchPriceActions();
        }
        return true;
    }

    private function matchPercentage() {
        foreach($this->accounts as $account) {
            $account->matchPercentage($this->config->btc_input, $this->holding_btc_weight_total);
        }
    }

    /**
     * @desc 更新账号组 info, 根据 info 判断 btc 总数是否正常，冻结总数是否正常，决定每个账号能执行的交易 第二步
     */
    private function matchInfo($force_update_info = false) {
        $frozen_btc = 0;
        $frozen_cny = 0;
        $btc_total = 0;
        $cny_total = 0;
        foreach($this->accounts as $account) {
            $account->updateInfo($force_update_info);
            $frozen_btc += $account->info['frozen_btc'];
            $frozen_cny += $account->info['frozen_cny'];
            $btc_total += $account->info['frozen_btc'] + $account->info['available_btc'];
            $cny_total += $account->info['frozen_cny'] + $account->info['available_cny'];
        }
        if($this->config->btc_input - $btc_total > $this->config->btc_assurance) {
            Mail::send('BTC total lose over limit', Logger::Log("BTC total lose over limit ". ($this->config->btc_input - $btc_total) .", check again."));
            return $this->matchInfo();
        }
        if($this->config->cny_input - $cny_total > $this->config->cny_assurance) {
            Mail::send('CNY total lose over limit', Logger::Log("CNY total lose over limit ". ($this->config->cny_input - $cny_total) .", check again."));
            return $this->matchInfo();
        }
        if($frozen_btc > $this->config->max_frozen_btc) {
            Mail::send('Frozen BTC is over limit', Logger::Log("Frozen BTC is over limit[$frozen_btc], check again."));
            return $this->matchInfo();
        }
        if($frozen_cny > $this->config->max_frozen_cny) {
            Mail::send('Frozen CNY is over limit', Logger::Log("Frozen CNY is over limit[$frozen_cny], check again."));
            return $this->matchInfo();
        }
        Logger::Log("Match actions finished.");
        /*
        foreach($this->accounts as $account) {
            Logger::Log($account->__toString());
        }
        */
        return true;
    }

    /**
     * @desc 生成出可以执行的交易，理论上在一次交易判断中同一个账号最多只能创建一个订单
     */
    private function generateTrade() {
        $available_accounts = $this->accounts;
        // 满足多市场组合算法
        $trades = array();
        while(count($available_accounts) > 0) {
            // 从账号组总弹出一个账号
            $account1 = array_shift($available_accounts);
            // 循环剩下的账号与其匹配
            foreach($available_accounts as $account2) {
                foreach($account1->actions as $action) {
                    // 第一个账号可以卖同时第二个市场也可以买
                    if($action === 'sell' && in_array('buy', $account2->actions)) {
                        $orders = array(
                            new OrderModel($account1, 'sell', $account1->price['buy'], $this->config->trans_unit),
                            new OrderModel($account2, 'buy', $account2->price['sell'], $this->config->trans_unit)
                        );
                        $trade = new TradeModel($orders);
                        Logger::Log('Generate 1 trade: ' . $trade->__toString());
                        $trades[] = $trade;
                    }
                    // 第一个账号可以买同时第二个市场也可以卖
                    if($action === 'buy' && in_array('sell', $account2->actions)) {
                        $orders = array(
                            new OrderModel($account2, 'sell', $account2->price['buy'], $this->config->trans_unit),
                            new OrderModel($account1, 'buy', $account1->price['sell'], $this->config->trans_unit)
                        );
                        $trade = new TradeModel($orders);
                        Logger::Log('Generate 1 trade: ' . $trade->__toString());
                        $trades[] = $trade;
                    }
                }
            }
        }
        // sort trade
        for ($i = 0; $i < count($trades); ++$i) {
            for ($j = 0; $j < $i; ++$j) {
                if ($trades[$j]->getSpread() < $trades[$j+1]->getSpread()) {
                    $temp = $trades[$j];
                    $trades[$j] = $trades[$j + 1];
                    $trades[$j + 1] = $temp;
                }
            }
        }
        return $trades;
    }

    private function validateTrade() {

    }
}