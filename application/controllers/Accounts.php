<?php

class AccountsController extends \Base\RestControllers {
    public function getAction() {
        $account_dao = new AccountDao();
        $account_group_dao = new AccountGroupDao('account-group');
        $params = $this->getRequest()->getParams();
        $param_id = array_search('edit', $params);
        if (!empty($param_id) && $params[$param_id] === 'edit') {
            $account_dao = new AccountDao();
            $market_dao = new MarketDao();
            $account_config_dao = new AccountConfigDao('account_config');
            $account_group_list = $account_group_dao->getList();
            $account = $account_dao->get($param_id);
            $market = $market_dao->get($account['market_id']);
            //account config
            $account_config_list = $account_config_dao->getConfigListByAccountId($param_id);
            $this->getView()->assign('account', $account);
            $this->getView()->assign('account_group_list', $account_group_list);
            $this->getView()->assign('account_config_list', $account_config_list);
            $this->getView()->assign('market', $market);
            $this->getView()->display('accounts-edit.phtml');
            exit;
        }
        if (array_key_exists('new', $params)) {
            $account_group_dao = new AccountGroupDao('account-group');
            $market_dao = new MarketDao();
            $account_group_list = $account_group_dao->getList();
            $market_list = $market_dao->getList();
            //account config
            $account_config_list = array();
            foreach ($market_list as $market) {
                $account_config_list[$market['name']] = get_account_config_keys($market['name']);
            }
            $this->getView()->assign('account_group_list', $account_group_list);
            $this->getView()->assign('account_config_list', $account_config_list);
            $this->getView()->assign('market_list', $market_list);
            $this->getView()->display('accounts-new.phtml');
            exit;
        }
        $this->getView()->assign('account_list', $account_dao->getAccountList());
        $this->getView()->display('accounts.phtml');
    }

    public function postAction() {
        $account_dao = new AccountDao();
        $account = array();
        $account['name'] = $this->getRequest()->getParam('name');
        $account['description'] = $this->getRequest()->getParam('description');
        $account['market_id'] = $this->getRequest()->getParam('market_id');
        $account['account_group_id'] = $this->getRequest()->getParam('account_group_id');
        $market_name = $this->getRequest()->getParam('market_name');
        $config_name_list = get_account_config_keys($market_name);
        $account_id = $account_dao->insertOne($account);
        $config_list = array();
        foreach ($config_name_list as  $config_name) {
            $config_list[] = array('name' => $config_name,
                'value' => $this->getRequest()->getParam($market_name.'_'.$config_name),
                'account_id' => $account_id
            );
        }
        $account_config_dao = new AccountConfigDao('account_config');
        $account_config_dao->insert($config_list);
        $this->redirect('/accounts');
    }

    public function putAction() {
        $account_dao = new AccountDao();
        $account = array();
        $account['id'] = $this->getRequest()->getParam('id');
        $account['name'] = $this->getRequest()->getParam('name');
        $account['description'] = $this->getRequest()->getParam('description');
        $market_name = $this->getRequest()->getParam('market_name');
        $account['account_group_id'] = $this->getRequest()->getParam('account_group_id');
        $config_list = array();
        $account_config_name_list = get_account_config_keys($market_name);
        foreach ($account_config_name_list as $account_config_name) {
            $config_list[] = array(
                'account_id' => $account['id'],
                'name' => $account_config_name,
                'value' => $this->getRequest()->getParam("{$market_name}_{$account_config_name}")
            );
        }
        $account_config_dao = new AccountConfigDao();
        foreach ($config_list as $config) {
            $account_config_dao->insert_update($config);
        }
        $account_dao->update($account);
        $this->redirect('/accounts');
    }
    public function deleteAction() {
        $id = $this->getRequest()->getParam('id');
        $account_dao = new AccountDao();
        $account_dao->delete($id);var_dump($account_dao->delete($id));
        $this->redirect('/accounts');
    }


}