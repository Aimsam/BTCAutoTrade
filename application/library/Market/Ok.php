<?php
namespace Market;

use Logger,
    BaseMarket,
    Curl,
    Yaf\Exception;

class Ok extends BaseMarket {

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
        $info['frozen_btc'] = $raw_info['funds']['freezed']['btc'];
        $info['available_cny'] = $raw_info['funds']['free']['cny'];
        $info['available_btc'] = $raw_info['funds']['free']['btc'];
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
        $order_list = $this->okCoin->getOrderList();
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
        return $this->okCoin->sell($price, $mount);
    }

    /**
     * @param $price
     * @param $mount
     * @return int | boolean
     */
    public function buy($price, $mount)
    {
        return $this->okCoin->buy($price, $mount);
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
            if ($this->okCoin->cancelorder($order['orders_id']) === false) {
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
        if ($this->okCoin->cancelorder($id) === false) {
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
        $content = $this->okCoin->getOrderInfo($id);
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
        $contents = $curl->get('https://www.okcoin.cn/api/depth.do');
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



/**
 * OkCoin 最专业的比特币交易平台 API
 * 文档地址: https://www.okcoin.cn/t-1000097.html
 *
 * 本接口需要PHP5以上 扩展 curl 和 json
 *
 * $info = OkCoin::getIns()->info();
Array
(
[funds] => Array
(
[free] => Array
(
[btc] => 2013.11
[cny] => 99999.9999
[ltc] => 0
)
[freezed] => Array
(
[btc] => 0
[cny] => 2013.17
[ltc] => 0
)
)
)
 *
 * $order_id = OkCoin::getIns()->buy(2013, 0.01);  买入 返回订单号
 * $order_id = OkCoin::getIns()->sell(8888, 0.01); 卖出 返回订单号
 *
 * $order_id = OkCoin::getIns()->cancelorder(1234567); 取消指定订单
 *
 * $orderList = OkCoin::getIns()->getOrderList();       返回订单列表
 * $orderInfo = OkCoin::getIns()->getOrderInfo(1234567); 返回指定订单信息
Array
(
[amount] => 0.01
[avg_rate] => 0
[deal_amount] => 0
[orders_id] => 1234567
[rate] => 2013
[status] => 0
[type] => buy
)
 * 订单状态说明 status 挂单状态 -1:已撤销 0:未成交 1:部分成交 2:完全成交
 *
 * @version 1.1
 * @author unspace
 * @广告 招聘高级/资深PHP工程师 mailto:jlnuwn@gmail.com
 *
 * @bitcoin 求捐赠 18bS9HypvFJaXyHj7Am8NvX2bZuPqdGECk
 */
class OkCoin {

    private static $obj_pool;
    private $partner;
    private $secretKey;
    private $last_error;

    private function __construct($access, $secret) {
        $this->partner = $access;
        $this->secretKey = $secret;
    }

    /**
     * 根据配置tag 单例返回对象
     * @return OkCoin
     */
    public static function getIns($access=null, $secret=null, $tag = 'default') {
        if (empty(self::$obj_pool[$tag])) {
            self::$obj_pool[$tag] = new self($access, $secret);
        }
        return self::$obj_pool[$tag];
    }

    /**
     * 获取帐号信息
     * @return array
     */
    public function info() {
        $url = 'https://www.okcoin.cn/api/userinfo.do';
        $post = array(
            'partner' => $this->partner,
        );
        $result = $this->request($url, $post);
        if (!$result || !$result['result']) {
            return false;
        }
        return $result['info'];
    }

    /**
     * 买入 下单 单独封装
     * @param float $rate 单价
     * @param float $amount 数量
     * @param string $symbol 当前货币兑 (btc_cny,ltc_cny)
     * @return long 返回订单号
     */
    public function buy($rate, $amount, $symbol = 'btc_cny') {
        return $this->trade('buy', $rate, $amount, $symbol);
    }

    /**
     * 卖出 下单 单独封装
     * @param float $rate 单价
     * @param float $amount 数量
     * @param string $symbol 当前货币兑 (btc_cny,ltc_cny)
     * @return long 返回订单号
     */
    public function sell($rate, $amount, $symbol = 'btc_cny') {
        return $this->trade('sell', $rate, $amount, $symbol);
    }

    /**
     * 买卖下单接口
     * @param string $type 买卖类型 buy/sell
     * @param float $rate 单价
     * @param float $amount 数量
     * @param string $symbol 当前货币兑 (btc_cny,ltc_cny)
     * @return long 订单号
     */
    public function trade($type, $rate, $amount, $symbol = 'btc_cny') {
        $rate = round($rate, 2);
        $amount = round($amount, 2);
        $url = 'https://www.okcoin.cn/api/trade.do';
        $post = array(
            'partner' => $this->partner,
            'symbol' => $symbol,
            'type' => $type,
            'rate' => $rate,
            'amount' => $amount,
        );
        $result = $this->request($url, $post);
        if (!$result || !$result['result']) {
            return false;
        }
        $log = $result['order_id']." [$type] [$rate*$amount] ".json_encode($post);
        //error_log(date('Y-m-d H:i:s').' '. $log."\n", 3, 'okcoin.log');
        return $result['order_id'];
    }

    /**
     * 取消订单
     * @param long $order_id 订单号
     * @param string $symbol 当前货币兑 (btc_cny,ltc_cny)
     * @return long 返回订单号
     */
    public function cancelorder($order_id, $symbol = 'btc_cny') {
        $url = 'https://www.okcoin.cn/api/cancelorder.do';
        $post = array(
            'partner' => $this->partner,
            'symbol' => $symbol,
            'order_id' => $order_id,
        );
        $result = $this->request($url, $post);
        if (empty($result)) {
            return false;
        }
        if (isset($result['errorCode']) && $result['errorCode'] === 10009) {

            return true;
        }
        $log = $result['order_id']." [cancel] ".json_encode($post);
        //error_log(date('Y-m-d H:i:s').' '. $log."\n", 3, 'okcoin.log');
        return $result['order_id'];
    }

    /**
     * 获取订单列表 (单独封装)
     * @param string $symbol 当前货币兑 (btc_cny,ltc_cny)
     * @return array  二维数组
     */
    public function getOrderList($symbol = 'btc_cny') {
        return $this->getorder(-1, $symbol);
    }

    /**
     * 获取订单信息 (单独封装)
     * @param long $order_id 订单号
     * @param string $symbol 当前货币兑 (btc_cny,ltc_cny)
     * @return array 一维数组
     */
    public function getOrderInfo($order_id, $symbol = 'btc_cny') {
        $result = $this->getorder($order_id, $symbol);
        return $result ? $result[0] : $result;
    }

    /**
     * 获取订单列表 (或指定订单信息)
     * @param type $order_id
     * @param type $symbol
     * @return array
     */
    public function getorder($order_id = -1, $symbol = 'btc_cny') {
        $url = 'https://www.okcoin.cn/api/getorder.do';
        $post = array(
            'partner' => $this->partner,
            'symbol' => $symbol,
            'order_id' => $order_id,
        );
        $result = $this->request($url, $post);
        if (!$result || !$result['result']) {
            if ($result['errorCode'] == '10009') {
                return array();
            }
            return false;
        }
        return $result['orders'];
    }

    /**
     * 获取错误号
     * @return string
     */
    public function getErrorNum() {
        return $this->last_error;
    }

    /**
     * 获取错误信息
     * @return string
     */
    public function getError() {
        $error_arr = array(
            '-2' => '本地参数配置错误',
            '-1' => '请求失败',
            '0' => '成功',
            '10000' => '必选参数不能为空',
            '10001' => '用户请求过于频繁',
            '10002' => '系统错误',
            '10003' => '未在请求限制列表中,稍后请重试',
            '10004' => 'IP限制不能请求该资源',
            '10005' => '密钥不存在',
            '10006' => '用户不存在',
            '10007' => '签名不匹配',
            '10008' => '非法参数',
            '10009' => '订单不存在',
            '10010' => '余额不足',
            '10011' => '买卖的数量小于BTC/LTC最小买卖额度',
            '10012' => '当前网站暂时只支持btc_cny ltc_cny',
            '10013' => '此接口只支持https请求',
        );
        if (isset($error_arr[$this->last_error])) {
            return $this->last_error . ':' . $error_arr[$this->last_error];
        } else {
            return '未定义错误:' . $this->last_error;
        }
    }

    //加签名 并发起请求
    private function request($url, $post) {
        if (empty($this->secretKey) || empty($this->partner)) {
            $this->last_error = -2;
            return false;
        }
        ksort($post);
        $param_str = http_build_query($post);
        $sign_str = $param_str . $this->secretKey;
        $sign = md5($sign_str);

        $post_str = $param_str . '&sign=' . strtoupper($sign);
        $bin = $this->curl_https_post($url, $post_str);
        if (!$bin) {
            $this->last_error = -1;
            return $bin;
        }

        $arr = json_decode($bin, true);
        if (!$arr || !$arr['result']) {
            $this->last_error = $arr['errorCode'];
//            Btc::log($this->getError($this->last_error), Btc::ERROR_LOG);
        } else {
            $this->last_error = 0;
        }
        return $arr;
    }

    //https post请求方法
    private function curl_https_post($url, $post_str) {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
        //curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 1);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $post_str);
        curl_setopt($ch, CURLOPT_USERAGENT, "Mozilla/4.0 (compatible; MSIE 7.0; Windows NT 5.1; SV1; .NET CLR 1.1.4322; .NET CLR 2.0.50727)");
        $bin = curl_exec($ch);
        curl_close($ch);
        return $bin;
    }

}
