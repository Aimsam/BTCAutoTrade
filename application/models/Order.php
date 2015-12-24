<?php
class OrderModel extends Thread {

    private $account;
    private $action;
    private $price;
    private $amount;
    // 交易平台上的 订单号
    private $order_id = null;
    public $status;

    // 挂单失败状态
    const ORDER_PLACE_FAILED = 0;
    // 挂单成功状态
    const ORDER_PLACE_SUCCESS = 1;
    // 交易成功状态
    const ORDER_DEAL_SUCCESS = 2;

    public static $order_type = array(
        'sell' => 0,
        'buy' => 1
    );

    /**
     * @param AccountDao $account
     * @param $action string buy or sell
     * @param $price float
     * @param $amount float
     */
    public function __construct(AccountModel $account, $action, $price, $amount) {
        $this->status = self::ORDER_PLACE_FAILED;
        $this->account = $account;
        $this->action = $action;
        $this->price = $price;
        $this->amount = $amount;
    }

    /**
     * @desc 执行交易
     */
    public function run() {
        Logger::Log('Start place Order: ' . $this->__toString());
        $action = $this->action;
        usleep(400000);
        $result = $this->account->market->$action($this->price, $this->amount);
        Logger::Log("order result :" . var_export($result));
        //false 代表下单不成功，直接再次尝试
        //TODO 如果非常长的时间整个报警
        while($result === false) {
            Logger::Log("Place " . get_class($this->account->market) . " order failed, try again.");
            $this->status = self::ORDER_PLACE_FAILED;
            return $this->run();
        }
        if($result === true) {
            Logger::Log("Place " . get_class($this->account->market) . " order successful and deal!");
            //下单成功并且交易成功
            $this->status = self::ORDER_DEAL_SUCCESS;
        } else {
            $this->order_id = $result;
            Logger::Log("Place " . get_class($this->account->market) . " order successful order_id[$this->order_id]");
            $this->status = self::ORDER_PLACE_SUCCESS;
        }
        return null;
    }

    public function checkDealing() {
        if($this->status === self::ORDER_DEAL_SUCCESS) {
            return true;
        } elseif($this->status === self::ORDER_PLACE_SUCCESS) {
            if($this->account->market->isDealing($this->order_id) === false) {
                $this->status = self::ORDER_DEAL_SUCCESS;
            } else {
                // 说明还在交易 不改状态
            }
        } else {
            throw new Exception('Place order failed or have no order placed.');
        }
        return null;
    }

    public function getAction() {
        return $this->action;
    }

    public function getAccount() {
        return $this->account;
    }

    public function getAmount() {
        return $this->amount;
    }

    public function getPrice() {
        return $this->price;
    }

    public function getId() {
        return $this->order_id;
    }

    public function __toString() {
        return get_class($this->account->market) . " $this->action Price: $this->price, Amount: $this->amount";
    }

    /**
     * @desc 存到数据库
     * @param $trade_id integer 交易 id
     */
    public function save($trade_id, $created = null) {
        if(!$created)
            $created = date('Y-m-d H:i:s');
        $order_attributes = array(
            'trade_id' => $trade_id,
            'price' => $this->price,
            'amount' => $this->amount,
            'created' => $created,
            'order_type' => self::$order_type[$this->action],
            'order_id' => $this->order_id,
            'account_id' => $this->account->getId()
        );
        $order_dao = new OrderDao();
        $order_dao->insertOne($order_attributes);
    }
}