<?php
class TradeModel {
    const TYPE_EARN = 0; // 盈利交易
    const TYPE_BALANCE = 1; // 平衡交易
    const TYPE_FORCE = 2; // 强制平衡交易

    const MODE_LOOSE = 0; // 宽松模式 只要挂单成功
    const MODE_STRICT = 1; // 严格模式 必须挂单成功以及交易成功
    private $orders = array();

    const TACTICS_TRANSPORT = 0;
    const TACTICS_HEDGE = 1;
    public $tactics = null;

    /**
     * @param $orders OrderModel
     * @param $spread float
     */
    public function __construct($orders) {
        Logger::Log("Generate a trade.");
        if(is_array($orders)) {
            $this->orders = $orders;
        } else {
            $this->orders[] = $orders;
        }
    }

    /**
     * @desc 搬砖策略判断
     */
    public function tacticsTransport($to_usd = 5.9, $to_cny = 6.0) {
        $this->tactics = self::TACTICS_TRANSPORT;
        $sell_price = 0;
        $buy_price = 0;
        $direction = false;
        if(count($this->orders) != 2) {
            throw new Exception('这个策略必须要2个单子哈~');
        }

        if($this->orders[0]->getAction() == 'sell') {
            if($this->orders[0]->getAccount()->market->to_cny_rate != 1) {
                $sell_price = $this->orders[0]->getPrice() / $this->orders[0]->getAccount()->market->to_cny_rate;
                // 加上手续费率
                $sell_price -= $sell_price * $this->orders[0]->getAccount()->market->fee;
                $buy_price = $this->orders[1]->getPrice();
                $direction = 'cny_to_usd';
            } else {
                $sell_price = $this->orders[0]->getPrice();
                $buy_price = $this->orders[1]->getPrice() / $this->orders[1]->getAccount()->market->to_cny_rate;
                // 加上手续费率
                $buy_price += $buy_price * $this->orders[1]->getAccount()->market->fee;
                $direction = 'usd_to_cny';
            }
        } else {
            if($this->orders[0]->getAccount()->market->to_cny_rate != 1) {
                $sell_price = $this->orders[1]->getPrice();
                $buy_price = $this->orders[0]->getPrice() / $this->orders[0]->getAccount()->market->to_cny_rate;
                // 加上手续费率
                $buy_price += $buy_price * $this->orders[0]->getAccount()->market->fee;
                $direction = 'usd_to_cny';
            } else {
                $sell_price = $this->orders[1]->getPrice() / $this->orders[1]->getAccount()->market->to_cny_rate;
                // 加上手续费率
                $sell_price -= $sell_price * $this->orders[1]->getAccount()->market->fee;
                $buy_price = $this->orders[0]->getPrice();
                $direction = 'cny_to_usd';
            }
        }
        if($direction === false || $sell_price == $buy_price) {
            throw new Exception('我日，没有外币市场你搬毛的砖?');
        }
        if($sell_price > 0 && $buy_price > 0) {
            if($direction == 'cny_to_usd') {
                $current_rate = $buy_price / $sell_price;
                $message = "CNY to USD judgement, current is $current_rate, need smaller than $to_usd, ";
                if($current_rate <= $to_usd) {
                    $message .= 'satisfied.';
                    Logger::Log($message);
                    return true;
                } else {
                    $message .= 'no satisfied.';
                    Logger::Log($message);
                    return false;
                }
            } else {
                $current_rate = $sell_price / $buy_price;
                $message = "USD to CNY judgement, current is $current_rate, need lager than $to_cny, ";
                if($current_rate >= $to_cny) {
                    $message .= 'satisfied.';
                    Logger::Log($message);
                    return true;
                } else {
                    $message .= 'no satisfied.';
                    Logger::Log($message);
                    return false;
                }
            }
        } else {
            throw new Exception('请问 0 是怎么粗来的');
        }
    }

    /**
     * @desc 对冲策略
     * @return bool
     */
    public function tacticsHedge() {
        $this->tactics = self::TACTICS_HEDGE;
        $sell_order = null;
        foreach($this->orders as $order) {
            if($order->getAction() == 'sell') {
                $sell_order = $order;
                break;
            }
        }
        if($this->getSpread() > $sell_order->getAccount()->market->reverse_spread) {
            $buy_order = null;
            foreach($this->orders as $order) {
                if($order->getAction() == 'buy') {
                    $buy_order = $order;
                    break;
                }
            }
            if($buy_order === null) {
                throw new Exception('No buy order in this trade.');
            }
            // 多币卖的情况
            if($buy_order->getAccount()->percentage < 1) {
                Logger::log('少B买的情况percentage:'.$buy_order->getAccount()->percentage);
                return true;
            // 少币卖的情况
            } elseif ($this->getSpread() > $buy_order->getAccount()->market->earn_spread) {
                Logger::log('多B买的情况'.$buy_order->getAccount()->market->earn_spread);
                return true;
            } else {
                Logger::Log("Account:\n[{$buy_order->getAccount()->__toString()}]\n hold btc percentage: [{$buy_order->getAccount()->percentage}]");
            }
        }
        return false;
    }

    /**
     * @param int $mode
     */
    public function run($mode = self::MODE_LOOSE) {
        foreach($this->orders as $order) {
            $order->start();
        }
        $place_start_time = time();
        // 小睡一下，等待挂单结果
        sleep(1);
        check_again:
        foreach($this->orders as $order) {
            if($order->status == OrderModel::ORDER_PLACE_FAILED) {
                // 如果超过市场的挂单市场限制放弃挂单，发邮件
                if(time() - $place_start_time > $order->getAccount()->market->place_order_timeout) {
                    Mail::send('Place order '.get_class($order->getAccount()->market).' timeout', Logger::log('Place order timeout, give up check:' . $order->__toString(), Logger::WARNING_LOG));
                    continue;
                }
                Logger::Log("Wait place order result.");
                sleep(1);
                goto check_again;
            }
            if($mode == self::MODE_STRICT) {
                // 所有单子必须全部交易成功 可以根据需要注释掉下面这个 while
                while($order->status !== OrderModel::ORDER_DEAL_SUCCESS) {
                    Logger::Log(get_class($order->getAccount()->market) . " order[". $order->getId() ."] is dealing, check again.");
                    $order->checkDealing();
                }
                Logger::Log(get_class($order->getAccount()->market) . " order deal success.");
            }

        }
        $this->save();
        // 交易完成之后刷新 info
        foreach($this->orders as $order) {
            $order->getAccount()->getInfo();
        }
        return true;
    }

    public function getSpread() {
        $sell_price = 0;
        $buy_price = 0;
        foreach($this->orders as $order) {
            if($order->getAction() == 'sell') {
                $sell_price = $order->getPrice();
                $sell_price -= $sell_price * $order->getAccount()->market->fee;
            } else {
                $buy_price = $order->getPrice();
                $buy_price += $buy_price * $order->getAccount()->market->fee;
            }
        }
        return $sell_price - $buy_price;
    }

    public function save() {
        $created = date('Y-m-d H:i:s');
        $trade_attributes = array(
            'created' => date('Y-m-d H:i:s'),
            'trade_type' => $this->tactics,
        );
        $trade_dao = new TradeDao();
        $trade_id = $trade_dao->insertOne($trade_attributes);
        foreach($this->orders as $order) {
            $order->save($trade_id, $created);
        }
    }

    public function __toString() {
        $string = array();
        foreach($this->orders as $order) {
            $string[] = $order->__toString();
        }
        return "Earn spread: [{$this->getSpread()}]\n" . implode("\n", $string);
    }
}