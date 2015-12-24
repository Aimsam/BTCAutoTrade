<?php
namespace Market;

use Logger,
    BaseMarket,
    Curl,
    Yaf\Exception;

class OkL extends Ok {

    private $okCoin;

    public function __construct($params = array()) {
        $special_config = array(
            'fee' => 0,
            'access' => null,
            'secret' => null,
        );
        $this->config = array_merge($this->config, $special_config);
        parent::__construct($params);
        $this->okCoin = $this->okCoin = OkCoin::getIns($this->access, $this->secret);
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
     *
     * @return array
     */
    public function getInfo()
    {
        $time = microtime(true);
        $raw_info = $this->okCoin->info();
        if (!isset($raw_info['funds'])) {
            return null;
        }
        $info['frozen_cny'] = $raw_info['funds']['freezed']['cny'];
        $info['frozen_btc'] = $raw_info['funds']['freezed']['ltc'];
        $info['available_cny'] = $raw_info['funds']['free']['cny'];
        $info['available_btc'] = $raw_info['funds']['free']['ltc'];
        $info['time'] = $time;
        return $info;
    }

    /**
     * @param bool $open_only
     * @return array
     */
    public function getOrders($open_only = true)
    {
        $timestamp = time();
        $order_list = $this->okCoin->getOrderList('ltc_cny');
        $return_result= array();
        $return_result['time'] = $timestamp;

        foreach ($order_list as $order) {
            $return_result['orders'][] = array(
                'price' => $order['rate'],
                'amount' => $order['amount'],
                'id' => $order['orders_id'],
                'type' => $order['type'],
            );
        }
        return $return_result;
    }

    /**
     * @param $price
     * @param $mount
     * @return int | boolean
     */
    public function sell($price, $mount)
    {
        return $this->okCoin->sell($price, $mount, 'ltc_cny');
    }

    /**
     * @param $price
     * @param $mount
     * @return int | boolean
     */
    public function buy($price, $mount)
    {
        return $this->okCoin->buy($price, $mount, 'ltc_cny');
    }

    /**
     * 取消所有订单
     * @return boolean
     */
    public function cancelAll()
    {
        $order_list = $this->getOrders();
        if (empty($order_list)) {
            return true;
        }
        foreach ($order_list as $order) {
            if ($this->okCoin->cancelorder($order['orders_id'], 'ltc_cny') === false) {
                return false;
            }
            sleep(1);
        }
        return true;
    }

    /**
     * 根据ID取消订单
     *
     * @param int
     * @return boolean
     */
    public function cancelOne($id)
    {
        if ($this->okCoin->cancelorder($id, 'ltc_cny') === false) {
            return false;
        }
        return true;
    }

    /**
     * 根据id判断是否交易成功，如果传入错误的ID应该返回false 交易成功返回false 交易失败返回true
     *
     * @param $id
     * @return mixed
     */
    public function isDealing($id)
    {
        $content = $this->okCoin->getOrderInfo($id, 'ltc_cny');
        if (!is_array($content)) {
            usleep(300000);
            return $this->isDealing($id);
        }
        if (!isset($content['status'])) {
            return false;
        } elseif ($content['status'] == 1 || $content['status'] == 2) {
            return true;
        }
        return false;
    }

    public function getPrice() {
        $time = microtime(true);
        $curl = new Curl();
        $contents = $curl->get('https://www.okcoin.cn/api/depth.do?symbol=ltc_cny');
        if (empty($contents)) {
            return $this->getPrice();
        }
        $array = json_decode($contents, true);
        if (!isset($array['bids']) || !isset($array['asks'])) {
            return $this->getPrice();
        }
        $price_list = array();
        $price_list['time'] = $time;
        $sell_list = array_reverse($array['asks']);
        $buy_list = $array['bids'];
        for ($i = 0; $i < 60; ++$i) {
            $price_list['buy'][] = array($buy_list[$i][0], $buy_list[$i][1]);
            $price_list['sell'][] = array($sell_list[$i][0],  $sell_list[$i][1]);
        }
        parent::setPrice($price_list);
        return $price_list;
    }

    public function getFee() {
        return $this->fee;
    }
}

