<?php

class LogController extends Base\RestControllers {
    //默认主页
    public function getAction() {
        $this->getRequest()->getParam('');
        $file = APPLICATION_PATH . '/log/'.date('Y-m-d').'.log';
        $logs = file_last_lines($file, 50);
        $this->getView()->assign('logs', $logs);
        $this->getView()->display('log.phtml');
    }

    public function indexAction() {
    }

    public function cliAction() {
    }

    public function log2inAction() {
    }
}