<?php

//initialize autloaders
require_once 'SplClassLoader.php';
$classLoader = new SplClassLoader('WrenchPusher', 'lib');
$classLoader->register();
$classLoader = new SplClassLoader('Wrench', 'vendor/Wrench/lib');
$classLoader->register();

//enter your ap key here.
define('APP_KEY', 'de504dc5763aeef9ff52');

//create the pusher connection
$client = new WrenchPusher\PusherClient(APP_KEY, array(
    'isSecure' => TRUE,
    'keepAliveDuration' => 5,
));
$client->connect();

//subscribe to a channel
$channelName = 'order_book';
$client->subscribeToChannel($channelName) or die("Error Subscribing to channel.");
//echo 'Subscribed to channel: ' . $channelName . "\n";
$redis = new Redis();
$redis->connect('127.0.0.1');
$redis->lPush('get_price_pids', getmypid());
// @todo Bitstamp CNY to USD rate
$rate = 6.1;
//let it listen forever
while (true) {
    $client->keepAlive();
    $responses = $client->receive();
    foreach ($responses as $response) {
        $data = (array)$response->getData();
        if (empty($data)) {
            continue;
        }
        $timestamp = time();
        $buy = array();
        $sell = array();
        for ($i = 0; $i < 10; ++$i) {
            $buy[] = array(floatval($data['asks'][$i][0]) * $rate, floatval($data['asks'][$i][1]));
            $sell[] = array(floatval($data['bids'][$i][0]) * $rate, floatval($data['bids'][$i][1]));
        }
        $price = array(
            'time' => $timestamp,
            'buy' => $buy,
            'sell' => $sell
        );
        $redis->set('Market\BitPrice', json_encode($price));
    }
}
?>
