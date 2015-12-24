<?php
namespace Market;

use Logger,
    BaseMarket,
    Curl,
    Yaf\Exception;

class Chbtc extends BaseMarket
{

    public function __construct($params = array())
    {
        $special_config = array(
            'access' => null,
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
        $time = time();
        $result = $this->request('getAccountInfo');
        if(array_key_exists('result', $result)) {
            $result = $result['result'];
            $info = array(
                'time' => $time,
                'frozen_cny' => $result['frozen']['CNY']['amount'],
                'frozen_btc' => $result['frozen']['BTC']['amount'],
                'available_cny' => $result['balance']['CNY']['amount'],
                'available_btc' =>$result['balance']['BTC']['amount']
            );
            return $info;
        } else {
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
    public function getPrice()
    {
        $url = 'http://api.chbtc.com/data/depth';
        $curl = new Curl();
        $time = time();
        $result = json_decode($curl->get($url), true);
        if (isset($result['bids']) && isset($result['asks'])) {
            $return_result['time'] = $time;
            $result['asks'] = array_reverse($result['asks']);
            for ($i = 0; $i < 50; ++$i) {
                $return_result['buy'][] = array($result['bids'][$i][0], $result['bids'][$i][1]);
                $return_result['sell'][] = array($result['asks'][$i][0], $result['asks'][$i][1]);
            }
            parent::setPrice($return_result);
            return $return_result;
        }
        //Logger::log('get China Price failed try again');
        return $this->getPrice();
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
            'price' => $price,
            'amount' => $amount,
            'tradeType' => 0,
            'currency' => 'btc'
        );
        $result = $this->request('order', $params);
        if($result['code'] == 1000 && array_key_exists('id', $result)) {
            return $result['id'];
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
            'price' => $price,
            'amount' => $amount,
            'tradeType' => 1,
            'currency' => 'btc'
        );
        $result = $this->request('order', $params);
        if($result['code'] == 1000 && array_key_exists('id', $result)) {
            return $result['id'];
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
        $params = array(
            'id' => $id,
            'currency' => 'btc'
        );
        $result = $this->request('cancelOrder', $params);
        if($result['code'] == 1000) {
            return true;
        }
        return false;
    }

    /**
     * 根据id判断是否交易成功，如果传入错误的ID应该返回false 交易成功返回false 交易失败返回true
     * @param $id
     * @return mixed
     */
    public function isDealing($id)
    {
        $params = array(
            'id' => $id,
            'currency' => 'btc'
        );
        $result = $this->request('getOrder', $params);
        // 挂单状态（0、待成交 1、取消 2、交易完成 3、待成交未交易部份）
        return $result['status'] == 0 ? true : false;
    }

    private function request($method, $params = array())
    {
        $curl = new Curl();
        $url = 'https://trade.chbtc.com/api/' . $method;
        $params = array_merge(
            array(
                'method' => $method,
                'accesskey' => $this->config['access']
            ),
            $params
        );
        $params_string = http_build_query($params);
        $sha_key = sha1($this->config['secret']);
        $py_script = APPLICATION_PATH . '/scripts/hmacSign.py';
        $command = "python $py_script '$params_string' '$sha_key'";
        exec($command, $sign);
        $time = intval(microtime(true) * 1000);
        $params['sign'] = $sign[0];
        $params['reqTime'] = $time;
        $result = json_decode($curl->get($url . '?' . http_build_query($params)), true);
        return $result;
    }
}