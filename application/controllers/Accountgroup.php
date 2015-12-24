<?php

class AccountGroupController extends \Base\RestControllers {
    public function getAction() {
        $account_group_dao = new AccountGroupDao('account_group');
        $params = $this->getRequest()->getParams();
        $handle = array_search('edit', $params);
        if (!empty($handle) && $params[$handle] === 'edit') {
            $account_group = $account_group_dao->get($handle);
            if (empty($account_group)) {
                $this->redirect('/account-group');
                exit;
            }
            $config_dao = new ConfigDao();
            $config_list = $config_dao->getConfigListByAccountGroupId($account_group['id']);
            $this->getView()->assign('config_list', $config_list);
            $this->getView()->assign('account_group', $account_group);
            $this->getView()->display('account-group-edit.phtml');
            exit;
        }
        if (array_key_exists('new', $params)) {
            $config_list = get_config_keys();
            $this->getView()->assign('config_list', $config_list);
            $this->getView()->display('account-group-new.phtml');
            exit;
        }
        $redis = Yaf\Registry::get('redis');
        if (!empty($params) && count($params) === 1) {
            $id = array_keys($params)[0];
            $config_dao = new ConfigDao();
            $config_list = $config_dao->getConfigListByAccountGroupId($id);
            $account_group_config = array();
            foreach ($config_list as $row) {
                $account_group_config[$row['name']] = $row['value'];
            }
            $account_dao = new AccountDao();
            $account_list = $account_dao->getAccountListByAccountGroupId($id);
            $market_dao = new MarketDao();
            $account_info_list = array();
            foreach ($account_list as $account) {
                $key = 'AccountInfo_'.$account['id'];
                $market = $market_dao->get($account['market_id']);
                $account_info_list[] = array_merge(json_decode($redis->get($key), true), array('market_name' => $market['name']));
            }
            $btc_price = json_decode($redis->get('Market\HuoLPrice'), true)['buy'][0][0];
            $this->getView()->assign('btc_price', $btc_price);
            $this->getView()->assign('account_group_config', $account_group_config);
            $this->getView()->assign('account_info_list', $account_info_list);
            $this->getView()->display('account-group-view.phtml');
            exit();
        }


        $this->getView()->assign('account_group_list', $account_group_dao->getList());
        $this->getView()->display('account-group.phtml');
    }

    public function postAction() {
        $account_group_dao = new AccountGroupDao('account_group');
        $account_group = array();
        $account_group['name'] = $this->getRequest()->getParam('name');
        $config_list = $this->getRequest()->getParam('config');
        $account_group_id = $account_group_dao->insertOne($account_group);
        $config_dao = new ConfigDao();

        foreach ($config_list as $key => $value) {
            $config_dao->insertOne(array(
                'value' => $value,
                'name' => $key,
                'account_group_id' => $account_group_id
            ));
        }
        $this->redirect('/account-group');
    }

    public function putAction() {
        $account_group_dao = new AccountGroupDao('account_group');
        $account_group = array();
        $account_group['id'] = $this->getRequest()->getParam('id');
        $account_group['name'] = $this->getRequest()->getParam('name');
        $config_list = $this->getRequest()->getParam('config');
        $config_dao = new ConfigDao();
        foreach ($config_list as $key => $value) {
            $config_dao->insert_update(array(
                'value' => $value,
                'name' => $key,
                'account_group_id' => $account_group['id']
            ));
        }
        $account_group_dao->update($account_group);
        $this->redirect('/account-group');
    }

    public function deleteAction() {
        $id = $this->getRequest()->getParam('id');
        $account_group_model = new AccountGroupDao('account_group');
        $account_group_model->delete($id);
        $this->redirect('/account-group');
    }


}