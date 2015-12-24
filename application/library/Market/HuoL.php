<?php

namespace Market;

use Logger,
    BaseMarket,
    Yaf\Exception;

//TODO error log
class HuoL extends Huo {
    private $post_param;
    private $api = 'https://api.huobi.com/apiv2.php';

    public function __construct($params = array()) {
        $special_config = array(
            'fee' => 0,
            'access' => null,
            'secret' => null,
        );
        $this->config = array_merge($this->config, $special_config);
        parent::__construct($params);
        $this->post_param = array(
            'method' => '',
            'access_key' => $this->config['access'],
        );
    }

    private function getSign($param, $order_param=array()) {
        $param['access_key'] = $this->config['access'];
        $param['secret_key'] = $this->config['secret'];
        foreach ($order_param as $key => $value) {
            $param[$key] = $value;
        }
        ksort($param);
        $pre_sign = http_build_query($param);
        return strtolower(md5($pre_sign));
    }

    private function request($method, $timestamp, $param=array()) {
        $curl = new Curl_HuoL();
        $curl->setHeader(array("Content-type: application/x-www-form-urlencoded"));
        $sign_param = array(
            'created' => $timestamp,
            'method' => $method,
        );
        $sign = $this->getSign($sign_param, $param);
        $this->post_param['method'] = $method;
        foreach ($param as $key => $value) {
            $this->post_param[$key] = $value;
        }
        $this->post_param['created'] = time();
        $this->post_param['sign'] = $sign;
        if (in_array($method, array('buy', 'sell'))) {
            $this->post_param['trade_password'] = '';
        }
        $result = $curl->post($this->api, $this->post_param);
        $result = json_decode($result, true);
        if(isset($result['code'])) {
            throw new Exception("error occurred, code: {$result['code']}, message: {$result['msg']}");
        }
        return $result;
    }

    /**
     * 获取账户信息
     * array(
     *      'time' => 123123213,
     *      'frozen_cny' => 123.23,
     *      'frozen_btc' => 0.1231,
     *      'available_cny' => 123.23,
     *      'available_btc' => 123
     * );
     * //如果有挂单frozen_cny 和 frozen_btc肯定不为空
     * @return array
     */
    public function getInfo() {
        $timestamp = time();
        try {
            $result = $this->request('get_account_info', $timestamp);
            //防止超过火币1秒限制
            if (array_key_exists('available_cny_display', $result)) {
                return array(
                    'time' => $timestamp,
                    'frozen_cny' => $result['frozen_cny_display'],
                    'frozen_btc' => $result['frozen_ltc_display'],
                    'available_cny' => $result['available_cny_display'],
                    'available_btc' => $result['available_ltc_display']
                );
            }
            return null;
        } catch (Exception $e) {
            Logger::Log($e->getMessage(), Logger::ERROR_LOG);
            return null;
        }
    }

    /**
     * 获取实时价格
     * array(
     *      'time' => 123123123,
     *      'buy' => array(
     *          price, amount
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
     * @return array
     */
    public function getPrice() {
        //        Btc::log('start get Huo Price');
        $time = time();
        $curl = new Curl_HuoL();
        $curl->setHeader(array("Content-type: application/x-www-form-urlencoded"));

        $content = $curl->get('http://market.huobi.com/market/huobi.php?a=detail_ltc&huobi_callback=view_detail_ltc&_=1398309854323');
        if (empty($content)) {
            return $this->getPrice();
        }
        preg_match("@view_detail_ltc\((.*)\)@", $content, $matches);
        if (!isset($matches[1])) {
            return $this->getPrice();
        }
        $result = json_decode($matches[1], true);
        $return_result = array();
        $return_result['time'] = $time;
        for ($i = 0; $i < 10; ++$i) {
            $return_result['buy'][] = array($result['buys'][$i]['price'], $result['buys'][$i]['amount']);
            $return_result['sell'][] = array($result['sells'][$i]['price'], $result['sells'][$i]['amount']);
        }
        parent::setPrice($return_result);
        return $return_result;
    }

    /**
     * 根据正在处理中的订单
     * @return array(
     *     'time' => 12312312,
     *     'orders' => array(
     *         array(
     *          'price' => 1234,
     *          'amount' => 123,
     *          'type' => 'buy'
     *          ),
     *          array(
     *          'price' => 1234,
     *          'amount' => 123,
     *          'type' => 'buy'
     *          ),
     *      )
     * )
     */
    function getOrders() {
        $timestamp = time();
        $result = $this->request('get_orders', $timestamp, array('coin_type' => '2'));
        var_dump($result);
        if (empty($result)) {
            return $this->getOrders();
        }
        $orders = array();
        foreach ($result as $order) {
            $orders[] = array(
                'id' => $order['id'],
                'price' => $order['order_price'],
                'amount' => $order['order_amount'],
                'type' => ($order['type'] == 2)?'sell':'buy'
            );
        }
        return array(
            'time' => $timestamp,
            'orders' => $orders,
        );
    }

    /**
     * @param $price
     * @param $amount
     * @return int | boolean
     */
    public function sell($price, $amount) {
        $timestamp = time();
        $result = $this->request('sell', $timestamp, array('coin_type' => '2', 'price'=>$price, 'amount' => $amount));
        if (empty($result)) {
            return $this->sell($price, $amount);
        }
        if (isset($result) && $result['result'] === 'success') {
            return $result['id'];
        }
        return false;
    }

    /**
     * @param $price
     * @param $amount
     * @return int | boolean
     */
    public function buy($price, $amount) {
        $timestamp = time();
        $result = $this->request('buy', $timestamp, array('coin_type' => '2', 'price'=>$price, 'amount' => $amount));
        if (empty($result)) {
            return $this->buy($price, $amount);
        }
        if (isset($result) && $result['result'] === 'success') {
            return $result['id'];
        }
        return false;
    }

    /**
     * 取消所有订单
     * @return boolean
     */
    public function cancelAll() {
        $order_list = $this->getOrders();
        foreach ($order_list['orders'] as $order) {
            $result = $this->cancelOne($order['id']);
            if ($result === false) {
                return false;
            }
        }
        return true;
    }

    /**
     * 根据ID取消订单
     * @param int
     * @return boolean
     */
    public function cancelOne($id) {
        $timestamp = time();
        $result = $this->request('cancel_order', $timestamp, array('id'=>$id, 'coin_type' => 2));
        if (empty($result)) {
            return $this->cancelOne($id);
        }
        if (isset($result['result']) && $result['result'] == 'success') {
            return true;
        }
        if (in_array($result['code'], array(42, 26, 4))) {
            return true;
        }
        return false;
    }

    /**
     * 根据id判断是否交易成功，如果传入错误的ID应该返回false 交易成功返回false 交易失败返回true
     * @param $id
     * @return mixed
     */
    public function isDealing($id) {
        sleep(2);
        $timestamp = time();
        $result = $this->request('order_info', $timestamp, array('id'=>$id, 'coin_type' => 2));
        if (empty($result)) {
            return $this->isDealing($id);
        }
        if (!isset($result['id'])) {
            return false;
        }
        if ($result['status'] == 1 || $result['status'] == 2) {
            return false;
        }
        return true;
    }
}


/**
 * session 级别的讨厌啊
 */
class Curl_HuoL {
    private $cookies = '';
    private $data;
    private $url = '';
    private $user_agent = 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_8_2) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/30.0.1599.101 Safari/537.36';
    private $time_out = 20;
    private $curl;
    private $http_code = 0;
    private $http_info = '';

    public function __construct() {
        $this->curl = curl_init();
    }

    public function get($url, array $data = null) {
        $this->url = $url;
        if (!empty($data)) $this->data =  http_build_query($data);
        curl_setopt($this->curl, CURLOPT_URL, $this->url);
        curl_setopt($this->curl, CURLOPT_POST, 0);
        return $this->httpRequest();
    }

    public function post($url, array $data = null) {
        $this->url = $url;
        $this->data = http_build_query($data);
        curl_setopt($this->curl, CURLOPT_URL, $this->url);
        curl_setopt($this->curl, CURLOPT_POST, 1);
        return $this->httpRequest();
    }

    public function clearCookies() {
        $this->cookies = '';
    }

    public function setCookies($cookies) {
        $this->cookies = $cookies;
    }

    public function getHttpInfo() {
        return $this->http_info;
    }

    public function getHttpCode() {
        return $this->http_code;
    }

    public function test() {
        var_dump($this->cookies);
    }

    public function setHeader($header) {
        curl_setopt($this->curl, CURLOPT_HTTPHEADER, $header);
    }

    private function httpRequest() {
        curl_setopt($this->curl, CURLOPT_HTTP_VERSION, CURL_HTTP_VERSION_1_0);
        curl_setopt($this->curl, CURLOPT_CONNECTTIMEOUT, $this->time_out);
        curl_setopt($this->curl, CURLOPT_TIMEOUT, $this->time_out);
        curl_setopt($this->curl, CURLOPT_SSL_VERIFYPEER, 0);
        curl_setopt($this->curl, CURLOPT_HEADER, 0);
        curl_setopt($this->curl, CURLOPT_USERAGENT, $this->user_agent);
        curl_setopt($this->curl, CURLOPT_COOKIEJAR, $this->cookies);
        curl_setopt($this->curl, CURLOPT_COOKIEFILE, $this->cookies);
        if (!empty($this->data)) {
            curl_setopt($this->curl, CURLOPT_POSTFIELDS, $this->data);
        }
        curl_setopt($this->curl, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($this->curl, CURLOPT_FOLLOWLOCATION, 1);
        $content = curl_exec($this->curl);
        $this->http_code = curl_getinfo($this->curl, CURLINFO_HTTP_CODE);
        $this->http_info = curl_getinfo($this->curl);
        curl_close($this->curl);
        return $content;
    }
}