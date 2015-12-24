<?php

define("APPLICATION_PATH",  realpath(dirname(__FILE__) . '/../'));

$app  = new Yaf\Application(APPLICATION_PATH . "/conf/application.ini");

$app->bootstrap()
    ->run();
