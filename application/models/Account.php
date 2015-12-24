<?php

use
    Market\China,
    Market\Bit,
    Market\Huo;

/**
 * Class AccountModel
 * @author Tuzki
 * @desc 账号 Model 市场的一个对象属于其属性
 * @property
 */
class AccountModel {
    private $account_id;
    public $market;
    public $info;
    public $active = true;

    /**
     * @param null $account_id
     */
    public function setAccountId($account_id)
    {
        $this->account_id = $account_id;
    }

    /**
     * @return null
     */
    public function getAccountId()
    {
        return $this->account_id;
    }

    // 满足 limit_size 条件的 price
    public $price;

    // 可进行的交易
    public $actions = array();

    public $percentage = 1;

    function __construct($account_id=null) {
        if (!empty($account_id)) {
            $account_model = new AccountDao();
            $account = $account_model->get($account_id);
            if($account) {
                $this->account_id = $account_id;
                // 市场类名
                $market_name = $account->market['name'];
                $market_name = 'Market\\'.$market_name;
                // 市场配置
                $account_config = array();
                $account_config_model = new AccountConfigDao();
                foreach($account_config_model->getConfigListByAccountId($account['id']) as $config) {
                    $account_config[$config['name']] = format_val($config['value']);
                }
                try {
                    $this->market = new $market_name($account_config);
                } catch (Exception $e) {
                    throw new Exception("API {$market_name} not found.");
                }
            } else {
                throw new Exception('Account not found.');
            }
        }
    }

    /**
     * @return mixed
     * @desc 因为 redis 中可能存有同一市场的多个账号 info
     */
    public function getInfo() {
        Logger::Log("Update ". get_class($this->market) . " account info.");
        $time = time();
        $info = null;
        while (!$info) {
            if (time() - $time > 60)
            {
                Logger::Log("Get ". get_class($this->market) . " account info failed over 60 secs give up.");
                $this->active = false;
                // 用上一次缓存的info
                $this->info = json_decode(Yaf\Registry::get('redis')->get("AccountInfo_{$this->account_id}"), true);
                return $this->info;
            }
            $info = $this->market->getInfo();
            if (!$info) {
                Logger::Log("Get ". get_class($this->market) . " account info failed try again later.");
                sleep(1);
            }
        }
        $this->info = $info;
        $this->saveInfo();
        Yaf\Registry::get('redis')->set("AccountInfo_{$this->account_id}", json_encode($info));
        return $info;
    }

    public function getCacheInfo() {
        // 此方法可能会无限循环占用 CPU，睡半秒
        usleep(500000);
        $cache_info = json_decode(Yaf\Registry::get('redis')->get("AccountInfo_{$this->account_id}"), true);
        // 1分钟以内的 info
        if($cache_info['time'] > 0 && time() - $cache_info['time'] < 60) {
            $this->info = $cache_info;
            return $cache_info;
        }
        return $this->getInfo();
    }

    /**
     * @param $trans_unit float account_group 的交易单位配置
     */
    public function updateInfo($force_update_info = false) {
        //Logger::Log("Start get " . get_class($this->market) . " info");
        if($force_update_info) {
            $this->info = $this->getInfo();
        } else {
            $this->info = $this->getCacheInfo();
        }
    }

    /**
     * @desc 返回该账号当前持币占最佳状态的多少（小于1 说明少了，大于1 说明多了）
     * @param $btc_total float 账号组投入币总数
     * @param $holding_btc_weight_total integer 账号组总持币权重
     * @return float
     */
    public function matchPercentage($btc_total_input, $holding_btc_weight_total) {
        // 当前持币数
        $self_btc_total = $this->info['available_btc'] + $this->info['frozen_btc'];
        // 最佳持币数
        $self_full_btc_total = $btc_total_input * ($this->market->holding_btc_weight / $holding_btc_weight_total);
        $percentage = $self_btc_total / $self_full_btc_total + 0.001;
        $this->percentage = $percentage;
        return $percentage;
    }

    /**
     * @desc 根据时间以及 limit_size 找出满足 size 卖买价格
     * @param $trans_unit float 交易单位
     */
    public function matchPriceActions($trans_unit) {
        // 此方法可能会无限循环占用 CPU，睡半秒
        usleep(500000);
        if (!$this->active) {
            return false;
        }
        // 判断价格是否超时
        $latest_price = $this->market->getCachePrice();
        if(time() - $latest_price['time'] > $this->market->price_duration) {
            Logger::Log(get_class($this->market) . ' Price is out of date[' . date('Y-m-d H:i:s', $latest_price['time']) . '], give up this market.');
            //return $this->matchPrice();
            return false;
        }
        unset($latest_price['time']);
        $price = array();

        // 判断 size 是否足够
        foreach($latest_price as $action => $price_array) {
            $size = 0;
            array_pop($price_array);
            foreach($price_array as $depth => $single_price) {
                //取深度
                if ($depth < 1) {
                    $size += $single_price[1];
                    continue;
                }
                $size += $single_price[1];
                if($size >= $this->market->limit_size) {
                    $price[$action] = $single_price[0];
                    break;
                }
                // 循环到最后一个还没有满足条件的时候说明取出的所有档位 size 之和都不满足重新取
                if($depth >= count($price_array) - 1) {
                    Logger::Log(get_class($this->market) . " {$action} Order size is not enough [{$depth}, {$size}], give up this market.");
                    //return $this->matchPrice();
                    return false;
                }
            }
        }
        $this->price = $price;
        Logger::Log(get_class($this->market) . " matched Price\n". var_export($this->price, true));
        // 可用 CNY 小于单次交易所需的 CNY 就只能卖
        if($this->info['available_cny'] > ($this->price['sell'] * $trans_unit)) {
            $this->actions[] = 'buy';
            // 可用 BTC 小于两倍交易单位就只能买
        }
        if($this->info['available_btc'] > (2 * $trans_unit)) {
            $this->actions[] = 'sell';
        }
        Logger::Log(get_class($this->market) . ' available actions: '.json_encode($this->actions));
        return $this->price;
    }

    public function getId() {
        return $this->account_id;
    }

    public function saveInfo() {
        $info_attributes = $this->info;
        $info_attributes['created'] = date('Y-m-d H:i:s', $info_attributes['time']);
        $info_attributes['account_id'] = $this->account_id;
        unset($info_attributes['time']);
        $accountInfo_dao = new AccountInfoDao();
        $accountInfo_dao->insertOne($info_attributes);
    }

    public function __toString() {
        $string = "\n" . get_class($this->market) . " Account INFO: " . var_export($this->info, true);
        //$string .= "\n Matched Price: " . var_export($this->price, true);
        $string .= "\n Available Actions: " . var_export($this->actions, true);
        return $string;
    }
}