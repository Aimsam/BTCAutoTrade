<?php

class OrdersController extends \Base\RestControllers {
    public function getAction() {
        $orders_dao = new OrderDao();
        $order_list = $orders_dao->getList(100)->order('id desc');
        $account_dao = new AccountDao();
        $account_list = $account_dao->getList();
        $temp = $account_list;
        foreach($temp as $row) {
            $account_list[$row['id']] = $row['name'];
        }

        $this->getView()->assign('order_list', $order_list);
        $this->getView()->assign('account_list', $account_list);
        $this->getView()->display('index.phtml');
    }

    public function postAction() {
    }

    public function putAction() {
    }

    public function deleteAction() {
    }


}