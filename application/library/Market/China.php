<?php
namespace Market;


use Logger,
    BaseMarket,
    Curl,
    Yaf\Exception;

class China extends BaseMarket {
    /**
     * array(
     *      'time' => 123123213,
     *      'cny' => 123.23,
     *      'btc' => 0.0001,
     *      'frozen_cny' => 123.23,
     *      'frozen_btc' => 0.1231,
     *      'available_cny' => 123.23,
     *      'available_btc' => 123
     * );
     * //如果有挂单frozen_cny 和 frozen_btc肯定不为空
     *
     * @return array
     */
    protected $curl = null;

    public function __construct($params = array()) {
        $special_config = array(
            'fee' => 0.003,
            'access' => null,
            'secret' => null,
        );
        $this->config = array_merge($this->config, $special_config);
        //print_vars($params);
        parent::__construct($params);
    }
    // use API
    public function getInfo()
    {
        $return_result = array();
        $time_start = microtime(true);
        $result = $this->request('getAccountInfo');
        if(isset($result['result']['balance']) && $result['result']['frozen']) {
            $return_result['time'] = $time_start;
            $return_result['available_cny'] = floatval(str_replace(',', '', $result['result']['balance']['cny']['amount']));
            //$return_result['available_btc'] = floatval($result['result']['balance']['btc']['amount']) - 2.62;
            $return_result['available_btc'] = floatval($result['result']['balance']['btc']['amount']);
            $return_result['frozen_cny'] = floatval(str_replace(',', '', $result['result']['frozen']['cny']['amount']));
            $return_result['frozen_btc'] = floatval($result['result']['frozen']['btc']['amount']);
            //$this->redis->set(Btc::$config_name.'_China_info', json_encode($return_result));
            return $return_result;
        } else {
            return null;
        }
    }

    /**
     * array(
     *      'time' => 123123123,
     *      'buy' => array(
     *          array(2323, 232), 买1
     *          array(2323, 232),
     *          array(2323, 232),
     *          array(2323, 232),
     *          array(2323, 232),
     *      ),
     *      'sell' => array(
     *          array(2323, 232), 卖1
     *          array(2323, 232),
     *          array(2323, 232),
     *          array(2323, 232),
     *          array(2323, 232),
     *      ),
     * );
     *
     * @return array
     */
    public function getPrice() {
        $time = time();
        $this->curl = new Curl();
        $url = 'https://data.btcchina.com/data/orderbook';
        $return_result = array();
        $result = json_decode($this->curl->get($url), true);
        if(isset($result['bids']) && isset($result['asks'])) {
            $return_result['time'] = $time;
            $result['asks'] = array_reverse($result['asks']);
            for ($i = 0; $i < 60; ++$i) {
                $return_result['buy'][] = array($result['bids'][$i][0], $result['bids'][$i][1]);
                $return_result['sell'][] = array($result['asks'][$i][0],  $result['asks'][$i][1]);
            }
            parent::setPrice($return_result);
            return $return_result;
        }
        //Logger::log('get China Price failed try again');
        return $this->getPrice();
    }

    /**
     * array(
     *      'time'=>123123213,
     *      'type'=>'buy',
     *      'price'=>1231,
     *      'amount'=>2323
     * );
     *
     * @return array
     */
    public function getOrders($open_only = true)
    {
        $return_result = array();
        $return_result['time'] = microtime(true);
        $return_result['orders'] = false;
        $params = array();
        if($open_only === false) {
            $params['openonly'] = false;
        }
        $result = $this->request('getOrders', $params);
        //var_dump($result);
        if(isset($result['result']['order'])) {
            $return_result['orders'] = array();
            foreach($result['result']['order'] as $order) {
                $return_result['orders'][] = array(
                    'id' => $order['id'],
                    'type' => $order['type'] == 'ask' ? 'sell' : 'buy',
                    'price' => $order['price'],
                    'amount' => $order['amount']
                );
            }
        }
        return $return_result;
    }

    // 获取交易记录 即 成功的订单
    public function getTransactions($type = 'all', $limit = 10) {
        if($type == 'buy') {
            $type  = 'buybtc';
        } elseif($type == 'sell') {
            $type = 'sellbtc';
        }
        $params = array(
            'type' => $type,
            'limit' => $limit
        );
        $return_result = array();
        $return_result['time'] = microtime(true);
        $result = $this->request('getTransactions', $params);
        if(isset($result['result']['transaction'])) {
            foreach($result['result']['transaction'] as $order) {
                $order['type'] = $order['type'] == 'buybtc' ? 'buy' : $order['type'];
                $order['type'] = $order['type'] == 'sellbtc' ? 'sell' : $order['type'];
                $return_result['orders'][] = array(
                    'id' => $order['id'],
                    'type' => $order['type'],
                    'price' => abs($order['cny_amount'] / $order['btc_amount']),
                    'amount' => abs($order['btc_amount'])
                );
            }
            return $return_result;
        }
        Logger::log('get China transactions failed, try again.', Logger::WARNING_LOG);
        return $this->getTransactions();
        //var_dump($this->request('getTransactions', $params));
    }

    /**
     * @param $price
     * @param $mount
     * @return boolean
     */
    public function sell($price, $amount)
    {
        $params = array(
            $price, $amount
        );
        $result = $this->request('sellOrder2', $params);
        // 挂单成功
        if(intval($result['result']) > 0) {
            return intval($result['result']);
        }
        return false;
    }

    /**
     * @param $price
     * @param $mount
     * @return boolean
     */
    public function buy($price, $amount)
    {
        $params = array(
            $price, $amount
        );
        $result = $this->request('buyOrder2', $params);
        // 挂单成功
        if(intval($result['result']) > 0) {
            return intval($result['result']);
        }
        return false;
    }

    public function queryOpenOrderByDetail($type, $price, $amount) {
        $order_result = $this->getOrders();
        if($order_result['orders'] === false) {
            Logger::log("query order failed try again.");
            return $this->queryOpenOrderByDetail($type, $price, $amount);
        }
        foreach($order_result['orders'] as $order) {
            // 交易中的订单，只要满足类型 价格 数量一致就返回匹配的 ID 为查询的订单 ID
            if($order['price'] == $price && $order['type'] == $type && $order['amount'] == $amount) {
                return $order['id'];
            }
        }
        // 没找到就说明已经交易成功返回 True
        return true;
    }

    public function queryOpenOrderById($order_id) {
        $order_result = $this->getOrders();
        if($order_result['orders'] === false) {
            echo "query order failed try again.\r\n";
            return $this->queryOpenOrderById($order_id);
        }
        foreach($order_result['orders'] as $order) {
            // 如果未成交订单中找到这个 id 的订单就返回订单详情
            if($order['id'] == $order_id) {
                return $order;
            }
        }
        // 没找到就返回 false，不合法或者已成交
        return false;
    }

    /**
     * 取消所有订单
     * @return boolean
     */
    public function cancelAll()
    {
        $orders = $this->getOrders();
        $success = true;
        // 只要有一个订单取消失败就视为失败
        foreach($orders['orders'] as $order) {
            if($this->cancelOne($order['id']) === false) {
                $success = false;
            }
        }
        return $success;
    }

    /**
     * @param integer
     * @return boolean
     */
    public function cancelOne($order_id) {
        $params = array(
            'id' => $order_id
        );
        $result = $this->request('cancelOrder', $params);
        if($result['result'] == true){
            return true;
        }
        return false;
    }

    /**
     * 是否正在有交易
     * 依次检测getOrder 为空 frozen_cny、frozen_btc 为空
     * 主程序调用完这个函数之后应该再getInfo验证余额是否是期待中的才算成功交易，误差应该再+ -一分
     *
     * @return boolean
     */
    public function isDeal()
    {
        $order = $this->getOrders();
        // 如果数组里面没有order 说明取失败了，直接返回true 不能进行新的交易
        if($order['orders'] === false) {
            echo "query order failed try again.\r\n";
            return $this->isDeal();
        }
        if(count($order['orders']) > 0) {
            return true;
        }
        return false;
    }

    public function isDealing($order_id) {
        /*
        $result = $this->queryOpenOrderById($order_id);
        // 订单号或者未成交中未找到，返回 false
        if($result === false) {
            return false;
        }
        // 找到了说明还在交易中，返回true
        return true;
        */
        $params = array(
            $order_id
        );
        $result = $this->request('getOrder', $params);
        if($result['result']['order']['status'] == 'open') {
            return true;
        }
        return false;
    }

    protected function signIn($method, $params = array()) {
        $mt = explode(' ', microtime());
        $ts = $mt[1] . substr($mt[0], 2, 6);

        $signature = http_build_query(array(
            'tonce' => $ts,
            'accesskey' => $this->config['access'],
            'requestmethod' => 'post',
            'id' => 1,
            'method' => $method,
            'params' => '',
        ));
        $signature .= implode(',', $params);
        //var_dump($signature);

        $hash = hash_hmac('sha1', $signature, $this->config['secret']);

        return array(
            'ts' => $ts,
            'hash' => $hash,
            'auth' => base64_encode($this->config['access'].':'. $hash),
        );
    }

    public function getFee() {
        return $this->fee;
    }

    protected function request($method, $params = array()){
        $sign = $this->signIn($method, $params);
        $postData = json_encode(array(
            'method' => $method,
            'params' => $params,
            'id' => 1,
        ));

        $headers = array(
            'Authorization: Basic ' . $sign['auth'],
            'Json-Rpc-Tonce: ' . $sign['ts'],
        );
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_USERAGENT,
            'Mozilla/4.0 (compatible; BTC China Trade Bot; '.php_uname('a').'; PHP/'.phpversion().')'
        );
        //var_dump($postData);
        curl_setopt($ch, CURLOPT_URL, 'https://api.btcchina.com/api_trade_v1.php');
        curl_setopt($ch, CURLOPT_POSTFIELDS, $postData);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);

        // run the query
        $res = curl_exec($ch);
        curl_close($ch);
        $res = json_decode($res, true);
        if(isset($res['error'])) {
            Logger::log("Request China API Error: {$res['error']['code']} {$res['error']['message']}", Logger::ERROR_LOG);
        }
        return $res;
    }
}
