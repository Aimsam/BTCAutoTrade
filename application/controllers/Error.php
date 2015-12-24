<?php

class ErrorController extends \Base\BaseControllers {
    public function errorAction() {
        $exception = $this->getRequest()->getException();
        switch ($exception->getCode()) {
            case YAF\ERR\NOTFOUND\VIEW:
            case YAF\ERR\NOTFOUND\CONTROLLER:
            case YAF\ERR\NOTFOUND\ACTION:
            case YAF\ERR\NOTFOUND\VIEW:
                $this->getView()->display('error.phtml');
                break;
            default :
                $message = $exception->getMessage();
                echo 0, ":", $exception->getMessage();
                break;
        }
    }

    public function authAction() {
        $this->getView()->display('auth.phtml');
    }

}