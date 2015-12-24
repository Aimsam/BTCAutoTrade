<?php

class TransportController extends Base\BaseControllers {
    public function runAction() {
        $account_group = new AccountGroupModel(1);
        $trades = $account_group->availableTrades();
        if(count($trades) > 0) {
            foreach($trades as $trade) {
                Logger::Log("Judge Trade: " . $trade->__toString());
                if($trade->tacticsTransport(6.01, 6.05)) {
                    $trade->run(TradeModel::MODE_LOOSE);
                    Logger::Log('finished 1 time, next trade.');
                    return $this->runAction();
                } else {
                    Logger::Log('no chance, next trade.');
                }
            }
            return $this->runAction();
        } else {
            throw new Exception('no possible trade, fix it!');
        }
    }



    public function exchangeAction() {
        $account_group_id = $this->getRequest()->getParam('account_group_id');
        $to_usd_rate = $this->getRequest()->getParam('to_usd_rate');
        $to_cny_rate = $this->getRequest()->getParam('to_cny_rate');

        if (empty($account_group_id) || empty($to_cny_rate) || empty($to_usd_rate)) {
            echo "param is empty \r\n";
            exit;
        }

        if ($to_cny_rate < 6 || $to_usd_rate > 6.3) {
            echo "error rate \r\n";
            exit;
        }
        $group_id_pid = Yaf\Registry::get('redis')->get('group_id_'.$account_group_id);
        if (!empty($group_id_pid)) {
            echo "kill progress $group_id_pid";
            system("kill -9 $group_id_pid \r\n");
            Yaf\Registry::get('redis')->del('group_id_'.$account_group_id);
        }
        Yaf\Registry::get('redis')->set('group_id_'.$account_group_id, getmypid());
        $this->exchange($account_group_id, $to_usd_rate, $to_cny_rate);
    }

    private function exchange($account_group_id, $to_usd_rate, $to_cny_rate) {
        $account_group = new AccountGroupModel($account_group_id);
        $trades = $account_group->availableTrades();
        if(count($trades) > 0) {
            foreach($trades as $trade) {
                Logger::Log("Judge Trade: " . $trade->__toString());
                if($trade->tacticsTransport($to_usd_rate, $to_cny_rate)) {
                    $trade->run(TradeModel::MODE_LOOSE);
                    Logger::Log('finished 1 time, next trade.');
                    return $this->runAction();
                } else {
                    Logger::Log('no chance, next trade.');
                }
            }
            return $this->exchange($account_group_id, $to_usd_rate, $to_cny_rate);
        } else {
            throw new Exception('no possible trade, fix it!');
        }
    }
}