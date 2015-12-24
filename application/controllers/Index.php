<?php

class IndexController extends Base\RestControllers {
    //默认主页
    public function getAction() {
        $this->forward('Index', 'Orders', 'get');
    }

    public function indexAction() {
        $account1 = new AccountDao(1);
        //$account2 = new AccountModel(3);

        //var_dump($account1->market->getAllConfig());
        var_dump($account1->matchPrice());
        //print_vars($account2->market->getInfo());
        $this->getView()->assign("content", "sss");
    }

    public function cliAction() {
        cli_echo("echo cli");
    }

    public function log2inAction() {

    }
}