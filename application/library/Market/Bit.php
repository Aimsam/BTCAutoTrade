<?php
namespace Market;

use Logger,
    BaseMarket,
    Curl,
    Yaf\Exception;

/**
 * Class BaseMarket
 * 市场基础
 * 一共9个接口 getOrders可以不实现
 */
class Bit extends BaseMarket {

    public function __construct(array $params = array()) {
        $special_config = array(
            'client_id' => null,
            'key' => null,
            'secret' => null,
            'fee' => 0.005,
            'to_cny_rate' => 6.1
        );
        $this->config = array_merge($this->config, $special_config);
        parent::__construct($params);
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
        $time = time();
        try {
            $balance = $this->request('balance');
        } catch(Exception $e) {
            return null;
        }
        $array = array(
            'time' => $time,
            'frozen_cny' => floatval($balance['usd_reserved']) * $this->to_cny_rate,
            'frozen_btc' => floatval($balance['btc_reserved']),
            'available_cny' => floatval($balance['usd_available']) * $this->to_cny_rate,
            'available_btc' => floatval($balance['btc_available']),
        );
        return $array;
    }

    /**
     * 获取实时价格
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
     * ){}
     * @return array
     */
    public function getPrice() {
        $url = 'https://www.bitstamp.net/api/order_book/';
        $curl = new Curl();
        $result = json_decode($curl->get($url), 'true');
        $return_result = array();
        if(isset($result['bids']) && isset($result['asks'])) {
            $return_result['time'] = intval($result['timestamp']);
            $depth = 0;
            foreach($result['bids'] as $buy) {
                if($depth > 9) break;
                $return_result['buy'][] = array(floatval($buy[0]) * $this->to_cny_rate, floatval($buy[1]));
                $depth += 1;
            }
            $depth = 0;
            foreach($result['asks'] as $sell) {
                if($depth > 9) break;
                $return_result['sell'][] = array(floatval($sell[0]) * $this->to_cny_rate, floatval($sell[1]));
                $depth += 1;
            }
            parent::setPrice($return_result);
            return $return_result;
        }
        return $this->getPrice();
    }

    /**
     * 根据正在处理中的订单
     * @param $id
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
    public function getOrders() {
        try {
            $result = $this->request('open_orders');
        } catch(Exception $e) {
            return array();
        }
        return $result;
    }

    /**
     * @param $price
     * @param $mount
     * @return int | boolean
     */
    public function sell($price, $mount) {
        // 卖单向下取整
        $price = floor($price / $this->to_cny_rate * 100) / 100;
        try {
            $result = $this->request('sell', array('amount' => $mount, 'price' => $price));
        } catch(Exception $e) {
            return false;
        }
        if(!isset($result['error'])) {
            return $result['id'];
        } else {
            return false;
        }
    }

    /**
     * @param $price
     * @param $mount
     * @return int | boolean
     */
    public function buy($price, $mount) {
        // 买单向上取整
        $price = ceil($price / $this->to_cny_rate * 100) / 100;
        try {
            $result = $this->request('buy', array('amount' => $mount, 'price' => $price));
        } catch(Exception $e) {
            return false;
        }
        if(!isset($result['error'])) {
            return $result['id'];
        } else {
            return false;
        }
    }

    /**
     * 取消所有订单
     * @return boolean
     */
    public function cancelAll() {}

    /**
     * 根据ID取消订单
     * @param int
     * @return boolean
     */
    public function cancelOne($id) {
        try {
            $result = $this->request('cancel_order', array('id' => $id));
        } catch(Exception $e) {
            return false;
        }
        if($result === true) {
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
        $open_orders = $this->getOrders();
        foreach($open_orders as $order) {
            // 找到并且 amount 和 交易单位一致 说明还未交易成功
            if($order['id'] == $id) {
                return true;
            }
        }
        return false;
    }

    /**
     * Bit::request()
     *
     * @param API Path $path
     * @param POST Data $req
     * @return Array containing data returned from the API path
     */

    public function getFee() {
        return $this->fee;
    }

    private function request($path, array $req = array()) {
        // API settings
        // generate a nonce as microtime, with as-string handling to avoid problems with 32bits systems
        $mt = explode(' ', microtime());
        $req['nonce'] = $mt[1] . substr($mt[0], 2, 6);
        $req['key'] = $this->key;
        $req['signature'] = $this->get_signature($req['nonce']);

        // generate the POST data string
        $post_data = http_build_query($req, '', '&');

        // any extra headers
        $headers = array();

        // our curl handle (initialize if required)
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/4.0 (compatible; MtGox PHP client; ' . php_uname('s') . '; PHP/' .
            phpversion() . ')');
        curl_setopt($ch, CURLOPT_URL, 'https://www.bitstamp.net/api/' . $path .'/');
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);  // man-in-the-middle defense by verifying ssl cert.
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);  // man-in-the-middle defense by verifying ssl cert.
        curl_setopt($ch, CURLOPT_POSTFIELDS, $post_data);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

        // run the query
        $res = curl_exec($ch);
        if ($res === false) {
            Logger::log('Bit Error, Could not get reply: ' . curl_error($ch), Logger::ERROR_LOG);
            throw new Exception('Could not get reply: ' . curl_error($ch));
        }
        curl_close($ch);
        $dec = json_decode($res, true);
        if (is_null($dec)) {
            Logger::log('Bit Error, Invalid data received, please make sure connection is working and requested API exists', Logger::ERROR_LOG);
            throw new Exception('Invalid data received, please make sure connection is working and requested API exists');
        }
        if (isset($dec['error'])) {
            throw new Exception('Bit return an error message: ' . $dec['error']);
        }
        return $dec;
    }

    /**
     * Bitstamp::get_signature()
     * Compute bitstamp signature
     * @param float $nonce
     */
    private function get_signature($nonce) {

        $message = $nonce.$this->client_id.$this->key;

        return strtoupper(hash_hmac('sha256', $message, $this->secret));

    }

}