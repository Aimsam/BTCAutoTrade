<?php
class LoginController extends Base\RestControllers {

    public function getAction() {
        $this->getView()->display('login.phtml');
    }

    public function postAction() {
        $password = $this->getRequest()->getPost('password');
        $username = $this->getRequest()->getPost('username');
        if (!empty($password) && !empty($username)) {
            $user_dao = new UserDao();
            if ($user_dao->login($username, $password)) {
                Yaf\Session::getInstance()->start();
                Yaf\Session::getInstance()->set('is_admin', $user_dao->getIsAdmin());
                $this->redirect('/');
            } else {
                $this->redirect('/login');
            }
        }
    }
}