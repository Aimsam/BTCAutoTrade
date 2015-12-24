<?php
namespace Market;

use Logger,
    BaseMarket,
    Curl,
    Yaf\Exception;

class Bitfinex extends BaseMarket
{
    public function __construct($params = array()) {
        $special_config = array(
            'fee' => 0.002,
            'to_cny_rate' => 6.2,
            'key' => null,
            'secret' => null,
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
    public function getInfo()
    {
        $return_result = array(
            'frozen_cny' => 0,
            'frozen_btc' => 0,
            'available_cny' => 0,
            'available_btc' => 0,
            'time' => time()
        );
        $result = $this->request('/balances');
        if ($result) {
            foreach ($result as $row) {
                if ($row['type'] == 'exchange') {
                    if ($row['currency'] == 'usd') {
                        $return_result['available_cny'] = $this->to_cny_rate * $row['amount'];
                    } elseif ($row['currency'] == 'btc') {
                        $return_result['available_btc'] = $row['amount'];
                    }
                }
            }
            $active_orders = $this->request('/orders');
            foreach ($active_orders as $order) {
                if ($order['symbol'] == 'btcusd') {
                    if ($order['side'] == 'buy') {
                        $cny = $this->to_cny_rate * ($order['price'] * $order['remaining_amount']);
                        $return_result['frozen_cny'] += $cny;
                        $return_result['available_cny'] -= $cny;
                    } elseif ($order['side'] == 'sell') {
                        $btc = $order['remaining_amount'];
                        $return_result['frozen_btc'] += $btc;
                        $return_result['available_btc'] -= $btc;
                    }
                }
            }
            return $return_result;
        }
        return null;
    }

    public function getPrice()
    {
        $url = 'https://api.bitfinex.com/v1/book/btcusd';
        $curl = new Curl();
        $time = time();
        $result = json_decode($curl->get($url), 'true');
        $return_result = array();
        if($result && isset($result['bids']) && isset($result['asks'])) {
            $return_result['time'] = $time;
            $depth = 0;
            foreach($result['bids'] as $buy) {
                if($depth > 9) break;
                $return_result['buy'][] = array(floatval($buy['price']) * $this->to_cny_rate, floatval($buy['amount']));
                $depth += 1;
            }
            $depth = 0;
            foreach($result['asks'] as $sell) {
                if($depth > 9) break;
                $return_result['sell'][] = array(floatval($sell['price']) * $this->to_cny_rate, floatval($sell['amount']));
                $depth += 1;
            }
            parent::setPrice($return_result);
            return $return_result;
        }
        return $this->getPrice();
    }
    
    function getOrders()
    {
        
    }

    /**
     * @param $price
     * @param $amount
     * @return int | boolean
     */
    public function sell($price, $amount)
    {
        $params = array(
            'symbol' => 'btcusd',
            'side' => 'sell',
            'price' => number_format($price / $this->to_cny_rate, 2, '.', ''),
            'amount' => number_format($amount, 2, '.', ''),
            'exchange' => 'bitfinex',
            'type' => 'exchange limit'
        );
        $result = $this->request('/order/new', $params);
        if ($result && @$result['order_id']) {
            return $result['order_id'];
        }
        Logger::log($result['message'], Logger::WARNING_LOG);
        return false;
    }

    /**
     * @param $price
     * @param $amount
     * @return int | boolean
     */
    public function buy($price, $amount)
    {
        $params = array(
            'symbol' => 'btcusd',
            'side' => 'buy',
            'price' => number_format($price / $this->to_cny_rate, 2, '.', ''),
            'amount' => number_format($amount, 2, '.', ''),
            'exchange' => 'bitfinex',
            'type' => 'exchange limit'
        );
        $result = $this->request('/order/new', $params);
        if ($result && @$result['order_id']) {
            return $result['order_id'];
        }
        Logger::log($result['message'], Logger::WARNING_LOG);
        return false;
    }

    /**
     * 取消所有订单
     * @return boolean
     */
    public function cancelAll()
    {
        
    }

    /**
     * 根据ID取消订单
     * @param int
     * @return boolean
     */
    public function cancelOne($id)
    {
    }

    /**
     * 根据id判断是否交易成功，如果传入错误的ID应该返回false 交易成功返回false 交易失败返回true
     * @param $id
     * @return mixed
     */
    public function isDealing($id)
    {
        $data = array(
            'order_id' => $id
        );
        $result = $this->request('/order/status', $data);
        if ($result['is_live']) {
            return true;
        }
        return false;
    }

    private function request($method, array $req = array())
    {
        // generate a nonce to avoid problems with 32bits systems
        $mt = explode(' ', microtime());
        $req['request'] = "/v1".$method;
        $req['nonce'] = $mt[1].substr($mt[0], 2, 6);

        // generate the POST data string
        $post_data = base64_encode(json_encode($req));

        $sign = hash_hmac('sha384', $post_data, $this->secret);

        // generate the extra headers
        $headers = array(
            'X-BFX-APIKEY: '.$this->key,
            'X-BFX-PAYLOAD: '.$post_data,
            'X-BFX-SIGNATURE: '.$sign,
        );

        // curl handle (initialize if required)
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_USERAGENT,
            'Mozilla/4.0 (compatible; Bter PHP bot; '.php_uname('a').'; PHP/'.phpversion().')'
        );

        curl_setopt($ch, CURLOPT_URL, 'https://api.bitfinex.com/v1'.$method);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $post_data);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);

        // run the query
        $res = curl_exec($ch);

        if ($res === false) {
            Logger::log('Curl error: '.curl_error($ch), Logger::ERROR_LOG);
            return false;
        }
        curl_close($ch);
        $dec = json_decode($res, true);
        // 订单返回为空的情况
//        if (!$dec) {
//            Logger::log('Invalid data: '.$res, Logger::ERROR_LOG);
//            return false;
//        }
        return $dec;
    }
}
