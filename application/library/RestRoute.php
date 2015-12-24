<?php

/**
 * Class RestRoute
 * @author aimsam
 * @desc 重写路由规则
 */
class RestRoute implements Yaf\Route_Interface {
    /**
     * <p><b>\Yaf\Route_Interface::route()</b> is the only method that a custom route should implement.</p><br/>
     * <p>if this method return TRUE, then the route process will be end. otherwise,\Yaf\Router will call next route in the route stack to route request.</p><br/>
     * <p>This method would set the route result to the parameter request, by calling \Yaf\Request_Abstract::setControllerName(), \Yaf\Request_Abstract::setActionName() and \Yaf\Request_Abstract::setModuleName().</p><br/>
     * <p>This method should also call \Yaf\Request_Abstract::setRouted() to make the request routed at last.</p>
     *
     * @link http://www.php.net/manual/en/yaf-route-interface.route.php
     *
     * @param \Yaf\Request_Abstract $request
     * @desc 默认module和controller均写死为Index了
     * @return bool
     */
    public function route($request)
    {
        $uris = explode('/', str_replace('-', '', $request->getRequestUri()));
        $request->setModuleName('Index');
        if (!empty($uris[1])) {
            $request->setControllerName($uris[1]);
        } else {
            $request->setControllerName('Index');
        }
        $params = array();
        $param_pairs = array_chunk(array_slice($uris, 2), 2);
        foreach ($param_pairs as $param) {
            $params[$param[0]] = empty($param[1])?'':$param[1];
        }
        $request->setParam($params);
        $request->setParam($_REQUEST);
        $request->setActionName(strtolower($request->getMethod()));
        $method = $request->getParam('_method');
        if (!empty($method)) {
            $request->setActionName(strtolower($method));
        }
        //如果不return true 会继续执行static 路由规则
        return true;
    }

    public function assemble(array $mvc, array $query = NULL) {
    }

}