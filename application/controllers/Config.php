<?php

class ConfigController extends \Base\RestControllers {
    public function getAction()
    {
        var_dump(get_config_keys());
        var_dump(get_account_config_keys('Huo'));

        parent::getAction();
    }

    public function postAction()
    {
        parent::postAction(); // TODO: Change the autogenerated stub
    }

    public function putAction()
    {
        parent::putAction(); // TODO: Change the autogenerated stub
    }

    public function deleteAction()
    {
    }


}