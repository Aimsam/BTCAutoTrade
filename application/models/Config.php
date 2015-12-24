<?php

/**
 * Class ConfigModel
 * @author Tuzki
 * @desc 配置类，对应 config 表，包含设置读取配置
 */
class ConfigModel {
    protected $config = array(
        'trans_unit' => 0.007, // 交易单位
        'btc_input'=> 0, // btc 投入
        'cny_input' => 0, // cny 投入

        // 同时下单的话趋势应该没必要了~
        //'trends' => 'balance', // 趋势 balance, up, down
        'max_frozen_cny' => 500, // 最大 CNY 冻结额
        'max_frozen_btc' => 0.1, // 最大 BTC 冻结额
        'force_times' => 10, // 强制平衡次数
        'cny_assurance' => 500, // CNY 余额保证(剩余总额不能比这个数字少)
        'btc_assurance' => 0.1, // BTC 余额保证(同上)
    );
    /**
     * @author Tuzki
     * @param integer
     */
    public function __construct($account_group_id) {
        $config_model = new ConfigDao();
        $config_list = $config_model->getConfigListByAccountGroupId($account_group_id);
        if(count($config_list) > 0) {
            foreach($config_list as $config) {
                if(array_key_exists($config['name'], $this->config)) {
                    $this->config[$config['name']] = format_val($config['value']);
                }
            }
        }
    }

    /**
     * @desc
     * @param $property
     * @return mixed
     */
    public function __get($property) {
        if(!array_key_exists($property, $this->config)) {
            throw new Exception("Account Group Config['$property'] has not exists.");
        }
        return $this->config[$property];
    }

    /**
     * @author Tuzki
     * @param $name string
     * @param $value mixed
     */
    public function set($name, $value) {
        if(array_key_exists($name, $this->config)) {
            $this->config[$name] = format_val($value);
        }
    }

    public function getAll() {
        return $this->config;
    }
}