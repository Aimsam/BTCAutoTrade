<?php

class MarketsController extends \Base\RestControllers {
    public function getAction() {
        $market_dao = new MarketDao();
        $params = $this->getRequest()->getParams();
        $handle = array_search('edit', $params);
        if (!empty($handle) && $params[$handle] === 'edit') {
            $market = $market_dao->get($handle);
            if (empty($market)) {
                $this->redirect('/markets');
                exit;
            }
            $this->getView()->assign('market', $market);
            $this->getView()->display('markets-edit.phtml');
            exit;
        }
        if (array_key_exists('new', $params)) {
            $this->getView()->display('markets-new.phtml');
            exit;
        }

        $this->getView()->assign('market_list', $market_dao->getList());
        $this->getView()->display('markets.phtml');
    }

    public function postAction() {
        $market_dao = new MarketDao();
        $markets = array();
        $markets['name'] = $this->getRequest()->getParam('name');
        $markets['description'] = $this->getRequest()->getParam('description');
        $markets['url'] = $this->getRequest()->getParam('url');
        $market_dao->insertOne($markets);
        $this->redirect('/markets');
    }

    public function putAction() {
        $market_dao = new MarketDao();
        $markets = array();
        $markets['id'] = $this->getRequest()->getParam('id');
        $markets['name'] = $this->getRequest()->getParam('name');
        $markets['description'] = $this->getRequest()->getParam('description');
        $markets['url'] = $this->getRequest()->getParam('url');
        $market_dao->update($markets);
        $this->redirect('/markets');
    }

    public function deleteAction() {
        $id = $this->getRequest()->getParam('id');
        $market_dao = new MarketDao();
        $market_dao->delete($id);
        $this->redirect('/markets');
    }


}