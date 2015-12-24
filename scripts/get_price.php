<?php
/**
 * 启动单个市场价格获取脚本
 */
$path = __DIR__;

$market_name = $argv[1];
system('/usr/local/bin/php '.$path.'/../public/cli.php "request_uri=/console/index/get_price?market='.$market_name.'" >> /dev/null &');
/*
if ($market_name == 'Bit') {
    system("php $path/bits/index.php >> /dev/null &");
} else {
    system('php '.$path.'/../public/cli.php "request_uri=/console/index/get_price?market='.$market_name.'" >> /dev/null &');
}
*/
