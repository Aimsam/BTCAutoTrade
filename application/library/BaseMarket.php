<?php
/**
 * Class BaseMarket
 * 市场基础
 * 一共9个接口 getOrders可以不实现
 */
abstract class BaseMarket {
    protected $config = array(
        'earn_spread' => 100,
        // 反搬盈利 可以为负
        'reverse_spread' => 0,
        'holding_btc_weight' => 50,
        'to_cny_rate' => 1, // 汇率
        'limit_size' => 5, // 挂单数量限制
        'price_duration' => 4, // 价格延迟
        'place_order_timeout' => 30, // 挂单尝试延迟 X 秒内未挂单成功，直接放弃
        'fee' => 0 // 手续费率
    );
    /**
     * @author Tuzki
     * @param array $params
     * @desc 用于设置市场的 API 等值
     */
    public function __construct(array $params = array()) {
        foreach($params as $key => $value) {
            if(array_key_exists($key, $this->config)) {
                $this->config[$key] = format_val($value);
            }
        }
    }

    public function __get($property) {
        if(!array_key_exists($property, $this->config)) {
            throw new Exception("Market Config $property is not exists.");
        }
        return $this->config[$property];
    }

    public function getAllConfig() {
        return $this->config;
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
    abstract public function getInfo();

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
    abstract public function getPrice();

    /**
     * @desc 将价格设置到缓存 子类调用此法
     * @param $price
     */
    public function setPrice($price) {
        Yaf\Registry::get('redis')->set(get_class($this)."Price", json_encode($price));
    }

    /**
     * 从缓存中获取价格
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
     * @return array
     */
    public function getCachePrice() {
        return json_decode(Yaf\Registry::get('redis')->get(get_class($this)."Price"), true);
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
    abstract function getOrders();

    /**
     * @param $price
     * @param $amount
     * @return int | boolean
     */
    abstract public function sell($price, $amount);

    /**
     * @param $price
     * @param $amount
     * @return int | boolean
     */
    abstract public function buy($price, $amount);

    /**
     * 取消所有订单
     * @return boolean
     */
    abstract public function cancelAll();

    /**
     * 根据ID取消订单
     * @param int
     * @return boolean
     */
    abstract public function cancelOne($id);

    /**
     * 根据id判断是否交易成功，如果传入错误的ID应该返回false 交易成功返回false 交易失败返回true
     * @param $id
     * @return mixed
     */
    abstract public function isDealing($id);

}