<?php
namespace Market;

use Logger,
    BaseMarket,
    Curl,
    Yaf\Exception;
class ChinaL extends China {

    public function getPrice() {
        $time = time();
        $this->curl = new Curl();
        $url = 'https://data.btcchina.com/data/orderbook?market=cnyltc';
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

    public function getInfo()
    {
        $return_result = array();
        $time_start = microtime(true);
        $result = $this->request('getAccountInfo');
        if(isset($result['result']['balance']) && $result['result']['frozen']) {
            $return_result['time'] = $time_start;
            $return_result['available_cny'] = floatval(str_replace(',', '', $result['result']['balance']['cny']['amount']));
            $return_result['available_btc'] = floatval($result['result']['balance']['ltc']['amount']);
            $return_result['frozen_cny'] = floatval(str_replace(',', '', $result['result']['frozen']['cny']['amount']));
            $return_result['frozen_btc'] = floatval($result['result']['frozen']['ltc']['amount']);
            return $return_result;
        } else {
            return null;
        }
    }

    /**
     * @param $price
     * @param $mount
     * @return boolean
     */
    public function sell($price, $amount)
    {
        $params = array(
            $price, $amount, 'LTCCNY'
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
            $price, $amount, 'LTCCNY'
        );
        $result = $this->request('buyOrder2', $params);
        // 挂单成功
        if(intval($result['result']) > 0) {
            return intval($result['result']);
        }
        return false;
    }

    public function getOrders($open_only = true)
    {
        $return_result = array();
        $return_result['time'] = microtime(true);
        $return_result['orders'] = false;
        $params = array(
            'market' => 'LTCCNY'
        );
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
            $type  = 'buyltc';
        } elseif($type == 'sell') {
            $type = 'sellltc';
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

    public function cancelOne($order_id) {
        $params = array(
            'id' => $order_id,
            'market' => 'LTCCNY'
        );
        $result = $this->request('cancelOrder', $params);
        if($result['result'] == true){
            return true;
        }
        return false;
    }

    protected function request($method, $params = array()) {

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