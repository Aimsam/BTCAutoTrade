<?php
/* example: php cli.php "request_uri=/command/account/index" */
define("APPLICATION_PATH",  realpath(dirname(__FILE__) . '/../'));

$app = new Yaf\Application(APPLICATION_PATH . '/conf/application.ini');
$app->bootstrap()->getDispatcher()->dispatch(new Yaf\Request\Simple());