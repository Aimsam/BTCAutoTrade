<?php
/**
 * 价格缓存脚本
 */
$path = __DIR__;
$market_list = array(
    'China',
    //'Bit',
    'Ok',
    'Huo',
    'Chbtc',
    'Btce',
    'Bitfinex'
);
$redis = new Redis();
$redis->connect('127.0.0.1');


if ($argv[1] == "start") {
    if ($redis->exists('get_price_pids')) {
        echo "get price daemon may already start.";
        exit;
    }
    echo "start\n\r";

    foreach ($market_list as $market) {
        echo "start market : $market \n\r";
        system("/usr/local/bin/php {$path}/get_price.php $market");
    }
    exit;
}

if ($argv[1] == "stop") {
    echo "stop\n\r";
    $pid = $redis->lPop('get_price_pids');
    while(!empty($pid)) {
        system("kill -9 $pid");
        echo "stop pid : $pid \n\r";
        $pid = $redis->lPop('get_price_pids');
    }
    exit;
}


echo "restart\n\r";
$pid = $redis->lPop('get_price_pids');
while(!empty($pid)) {
    system("kill -9 $pid");
    echo "stop pid : $pid \n\r";
    $pid = $redis->lPop('get_price_pids');
}

foreach ($market_list as $market) {
    echo "start market : $market \n\r";
    system("/usr/local/bin/php {$path}/get_price.php $market ");
}
exit;
