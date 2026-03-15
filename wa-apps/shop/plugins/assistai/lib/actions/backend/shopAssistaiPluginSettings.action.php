<?php

//основной экшен насроек
class shopAssistaiPluginSettingsAction extends waViewAction
{

    public function execute()
    {

        $app_config = wa()->getConfig()->getAppConfig('shop');
        $path = $app_config->getAppPath('plugins/assistai/templates/settings/Settings.html');
        $this->setTemplate($path);

        //Только наличие или отсутсвие токена
        $token = !empty(wa('shop')->getPlugin('assistai')->getSettings('token'));
        $email = wa('shop')->getPlugin('assistai')->getSettings('email');
        $password = wa('shop')->getPlugin('assistai')->getSettings('password');


        //Если есть токен, пытаемся получить данные.
        if ($token){
            $api = new shopAssistaiPluginApi();
            $settingsApi = $api->getSettings();
        }
        else{
            $settingsApi = [];
        }



        $this->view->assign('settingsApi', $settingsApi );
        $this->view->assign('token', $token);
        $this->view->assign('email', $email);
        $this->view->assign('password', $password);

    }


}