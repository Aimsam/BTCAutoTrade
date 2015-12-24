<?php
class HedgeController extends Base\BaseControllers {
    public function runAction($force_update = true) {
        $this->restart();
        ini_set('default_socket_timeout', -1);
        $account_group = new AccountGroupModel(1);
        $trades = $account_group->availableTrades($force_update);
        if(count($trades) > 0) {
            foreach($trades as $trade) {
                Logger::Log("Judge Trade: " . $trade->__toString());
                if($trade->tacticsHedge()) {
                    $trade->run(TradeModel::MODE_LOOSE);
                    Logger::Log('finished 1 time, next trade.');
                    return $this->runAction();
                } else {
                    Logger::Log('no chance, next trade.');
                }
            }
        } else {
            Logger::Log("No available trade, try again.");
        }
        // 释放 account group 对象
        unset($account_group);
        return $this->runAction(false);
    }

    private function restart() {
        //暂时做个定时重启策略
        $time = time();
        $pid = getmypid();
        $hedge = json_decode(Yaf\Registry::get('redis')->get('hedge'), true);
        if (empty($hedge)) {
            Yaf\Registry::get('redis')->set('hedge', json_encode(array(
                'time' => $time,
                'pid' => $pid
            )));
        } else {
            if ($time - $hedge['time'] > 500) {
                Logger::Log("restart Hedge");
                $path = APPLICATION_PATH."/scripts/Hedge";
                Yaf\Registry::get('redis')->del('hedge');
                system("$path");
                system("kill -9 {$hedge['pid']}");
                system("kill -9 {$pid}");
            } else {
                if ($hedge['pid'] != $pid) {
                    system("kill -9 {$hedge['pid']}");
                    Yaf\Registry::get('redis')->set('hedge', json_encode(array(
                        'time' => $time,
                        'pid' => $pid
                    )));
                }
            }
        }
    }
}
