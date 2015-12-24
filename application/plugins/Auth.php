<?php
/**
 * @name DefaultPlugin
 * @desc 默认插件
 * @author aimsam
 */
class AuthPlugin extends Yaf\Plugin_Abstract {

    public function routerStartup(Yaf\Request_Abstract $request, Yaf\Response_Abstract $response) {
    }

    public function routerShutdown(Yaf\Request_Abstract $request, Yaf\Response_Abstract $response) {
        //不需要权限验证的模块
        $no_require = array(
            'Login' => null,
        );

        //不需要管理员权限的模块
        $admin_require = array(
            'Log' => null,
            'Orders' => null,
            'Accountgroup' => null,
            'Index' => null
        );

        $is_admin = Yaf\Session::getInstance()->get('is_admin');

        //权限控制
        if (in_array($request->getControllerName(), array_keys($no_require))) {
            return;
        }

        if (!in_array($request->getControllerName(), array_keys($admin_require)) && $is_admin !== '1') {
            $request->setModuleName('Index');
            $request->setControllerName('Login');
            $request->setActionName('get');echo 3;die;
            return;
        }

        if (!in_array($request->getControllerName(), array_keys($admin_require)) && $is_admin === '0') {
            $request->setModuleName('Index');
            $request->setControllerName('Error');
            $request->setActionName('auth');
            return;
        }
    }

    public function dispatchLoopStartup(Yaf\Request_Abstract $request, Yaf\Response_Abstract $response) {
    }

    public function preDispatch(Yaf\Request_Abstract $request, Yaf\Response_Abstract $response) {
    }

    public function postDispatch(Yaf\Request_Abstract $request, Yaf\Response_Abstract $response) {
    }

    public function dispatchLoopShutdown(Yaf\Request_Abstract $request, Yaf\Response_Abstract $response) {
    }

}