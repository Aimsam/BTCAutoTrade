<?php

class Bootstrap extends Yaf\Bootstrap_Abstract {

    public function _initDb() {
        $db_config = Yaf\Application::app()->getConfig()->db;
        $pdo = new PDO("mysql:dbname={$db_config->dbname};host={$db_config->hostname};charset={$db_config->charset}", $db_config->user, $db_config->password);
        $db = new NotORM($pdo);
        Yaf\Registry::set('db', $db);
    }

    public function _initRedis() {
        $redis = new Redis();
        $redis->connect('127.0.0.1');
        Yaf\Registry::set('redis', $redis);
    }

    public function _initFunctions() {
        Yaf\Loader::import("functions.php");
    }

    public function _initDto() {
        $file_path = Yaf\Application::app()->getConfig()->application->path->dao;
        $file_list = scandir($file_path);

        foreach ($file_list as $file_name) {
            if (strpos($file_name, '.php') !== false) {
                Yaf\Loader::import("{$file_path}/{$file_name}");
            }
        }
    }

    public function _initRest(Yaf\Dispatcher $dispatcher) {
        $dispatcher->disableView();
        //如果不是cli就调用rest路由
        if (!$dispatcher->getRequest()->isCli()) {
            $router = $dispatcher->getRouter();
            $route = new RestRoute();
            $router->addRoute("rest", $route);
            //设置模板目录
            $view_engine = new Yaf\View\Simple(APPLICATION_PATH . '/application/views');
            $dispatcher->setView($view_engine);
            //启用权限控制插件
            $auth = new AuthPlugin();
            $dispatcher->registerPlugin($auth);
        } else {
            $command = new CommandLinePlugin();
            $dispatcher->registerPlugin($command);
        }
    }

    public function _initRouter(Yaf\Dispatcher $dispatcher) {


    }

    public function _initLocalNamespace() {

    }
}