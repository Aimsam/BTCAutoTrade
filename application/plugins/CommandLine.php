<?php
/**
 * Class     CommandLinePlugin
 * 命令行插件
 *
 * @author   yangyang3
 */
class CommandLinePlugin extends Yaf\Plugin_Abstract {

    public function routerStartup(Yaf\Request_Abstract $request, Yaf\Response_Abstract $response) {
    }

    public function routerShutdown(Yaf\Request_Abstract $request, Yaf\Response_Abstract $response) {
        /* 验证是否为命令行方式 */
        if ($request->isCli()) {
            $action = $request->getActionName();

            $locate_param = strpos($request->getRequestUri(), '?');
            $locate = strpos($request->getActionName(), '?');
            /* 验证action是否有传参 */
            if ($locate !== false) {
                $query_list = array();

                //重新设置action
                $request->setActionName(substr($action, 0, $locate));

                //截取query_string
                $query_string = substr($request->getRequestUri(), $locate_param + 1);

                //解析query_string
                parse_str($query_string, $query_list);

                //循环set到param
                foreach ($query_list as $key => $value) {
                    $request->setParam($key, $value);
                }
            }
        }

        $request_uri = strtolower($request->getModuleName() . '/' . $request->getControllerName() . '/' . $request->getActionName());

        $request->setRequestUri($request_uri);
        $request->setModuleName(ucfirst($request->getModuleName()));
        $request->setControllerName(underline_to_camel(ucfirst($request->getControllerName())));
        $request->setActionName(underline_to_camel($request->getActionName()));

        /* 保存请求地址 */
        Yaf\Registry::set('request_uri', $request_uri);
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