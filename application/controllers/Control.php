<?php

class ControlController extends \Base\RestControllers {
    public function getAction() {
        $params = $this->getRequest()->getParams();
        $params = array_flip($params);
        $script_path =  \Yaf\Application::app()->getAppDirectory()."/../scripts/";
        $return = array();

        if (in_array('cacheprice', $params)) {
            exec("/usr/local/bin/php {$script_path}cache_price.php", $return);
        }

        if (in_array('restart', $params)) {
            exec("/usr/local/bin/php {$script_path}/../public/cli.php 'request_uri=/console/Hedge/run' > {$script_path}/../log/hedge.log 2>&1 &", $return);
        }

        if (in_array('killall', $params)) {
            exec("killall php", $return);
        }

        if (in_array('exchange', $params)) {
            $account_group_id = $this->getRequest()->getParam('account_group_id');
            $to_usd_rate = $this->getRequest()->getParam('to_usd_rate');
            $to_cny_rate = $this->getRequest()->getParam('to_cny_rate');
            $log_path = \Yaf\Application::app()->getConfig()->application->path->log.'/'.$account_group_id.'.log';
            $cli = \Yaf\Application::app()->getConfig()->application->directory.'/../public/cli.php';

            $group_id_pid = Yaf\Registry::get('redis')->get('group_id_'.$account_group_id);
            if (!empty($group_id_pid)) {
                $return[] = "group $group_id_pid exchange will restart";
            }

            $param = "account_group_id=$account_group_id&to_usd_rate=$to_usd_rate&to_cny_rate=$to_cny_rate";
            exec('php '.$cli.' "request_uri=/console/Transport/exchange?'.$param.'" >> '.$log_path.' &');
            $return[] = 'exchange start up';
        }

        $this->getView()->assign('return', $return);
        $this->getView()->display('control.phtml');
    }

    public function postAction() {

    }

    public function putAction() {

    }

    public function deleteAction() {

    }


}
