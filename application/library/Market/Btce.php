<?php
namespace Market;

use Logger,
    BaseMarket,
    Curl,
    Yaf\Exception;

class Btce extends BaseMarket
{
    public function __construct($params = array()) {
        $special_config = array(
            'fee' => 0.002,
            'to_cny_rate' => 6.2,
            'key' => null,
            'secret' => null,
        );
        $this->config = array_merge($this->config, $special_config);
        //print_vars($params);
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
        $result = $this->request('getInfo');
        if ($result) {
            $return_result = array(
                'time' => $result['return']['server_time'],
                'frozen_cny' => 0,
                'frozen_btc' => 0,
                'available_cny' => $result['return']['funds']['usd'] * $this->to_cny_rate,
                'available_btc' => $result['return']['funds']['btc']
            );
            if ($result['return']['open_orders'] > 0) {
                $open_orders = $this->request('ActiveOrders', array('pair' => 'btc_usd'));
                foreach ($open_orders['return'] as $order) {
                    if ($order['type'] == 'sell') {
                        $return_result['frozen_btc'] += $order['amount'];
                    } else {
                        $return_result['frozen_cny'] += $this->to_cny_rate * $order['amount'] * $order['rate'];
                    }
                }
            }
            return $return_result;
        }
        return null;
    }

    public function getPrice()
    {
        $url = 'https://btc-e.com/api/2/btc_usd/depth';
        $curl = new Curl();
        $time = time();
        $result = json_decode($curl->get($url), 'true');
        $return_result = array();
        if($result && isset($result['bids']) && isset($result['asks'])) {
            $return_result['time'] = $time;
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
            'pair' => 'btc_usd',
            'type' => 'sell',
            'rate' => number_format($price / $this->to_cny_rate, 3, '.', ''),
            'amount' => $amount
        );
        $result = $this->request('Trade', $params);
        if ($result && $result['success'] == 1) {
            return $result['return']['order_id'];
        }
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
            'pair' => 'btc_usd',
            'type' => 'buy',
            'rate' => number_format($price / $this->to_cny_rate, 3, '.', ''),
            'amount' => $amount
        );
        $result = $this->request('Trade', $params);
        if ($result && $result['success'] == 1) {
            return $result['return']['order_id'];
        }
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
        $result = $this->request('ActiveOrders');
        if ($result['success'] == 1) {
            foreach ($result['return'] as $order_id => $activeOrder) {
                if ($order_id == $id) {
                    return true;
                }
            }
        }
        return false;
    }

    private function request($method, array $req = array())
    {
        $req['method'] = $method;
        $mt = explode(' ', microtime());
        $req['nonce'] = $mt[1];

        // generate the POST data string
        $post_data = http_build_query($req, '', '&');

        $sign = hash_hmac('sha512', $post_data, $this->secret);

        // generate the extra headers
        $headers = array(
            'Sign: '.$sign,
            'Key: '.$this->key,
        );

        // our curl handle (initialize if required)
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/4.0 (compatible; BTCE PHP client; '.php_uname('s').'; PHP/'.phpversion().')');
        curl_setopt($ch, CURLOPT_URL, 'https://btc-e.com/tapi/');
        curl_setopt($ch, CURLOPT_POSTFIELDS, $post_data);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);

        // run the query
        $res = curl_exec($ch);
        curl_close($ch);
        if ($res === false) {
            return false;
        }
        $dec = json_decode($res, true);
        if (!$dec) {
            return false;
        }
        return $dec;
    }
}
