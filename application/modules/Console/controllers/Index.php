<?php

class IndexController extends Base\BaseControllers {
    public function indexAction() {
        $account_group = new AccountGroupModel(1);
        var_dump($account_group->config->getAll());
        var_dump($account_group->accounts[0]->market->getAllConfig());
    }

    public function testAction() {
        $market_name = 'Market\\Bitfinex';
        $account_model = new AccountConfigDao();
        $account_config_list = $account_model->getConfigListByAccountId(10);
        $account_config_param = array();
        foreach ($account_config_list as $account_config) {
            $account_config_param[$account_config['name']] = $account_config['value'];
        }
        $market = new $market_name($account_config_param);
//        var_dump($market->sell(5060.51, 0.01));
//        var_dump($market->cancelOne(6371064));
//var_dump($market->getPrice());
        var_dump($market->getInfo());
//        var_dump($market->getCachePrice());
//        var_dump($market->getOrders());
//        var_dump($market->buy(360.51, 0.01));
//        var_dump($market->isDealing(7793884));
    }

    /**
     * @desc 获取价格Action
     * @param string market
     */
    public function getPriceAction() {
        Yaf\Registry::get('redis')->lPush('get_price_pids', getmypid());
        $market_name = $this->getRequest()->getParam('market');
        $market_name = "Market\\$market_name";
        $market = new $market_name();
        while (true) {
            if ($market_name == 'Market\Btce') {
                $account_config = array();
                $account_config_model = new AccountConfigDao();
                foreach($account_config_model->getConfigListByAccountId(9) as $config) {
                    $account_config[$config['name']] = format_val($config['value']);
                }
                $market = new $market_name($account_config);
            }
            if ($market_name == 'Market\Bitfinex') {
                $account_config = array();
                $account_config_model = new AccountConfigDao();
                foreach($account_config_model->getConfigListByAccountId(10) as $config) {
                    $account_config[$config['name']] = format_val($config['value']);
                }
                $market = new $market_name($account_config);
            }         
	    usleep(Yaf\Application::app()->getConfig()->console->price_sleep_delay);
            $market->getPrice();
        }
    }

    public function testMailAction() {
        Mail::send('AutoTrade is down fuck you!!', 'AutoTrade is down!');
    }
}
