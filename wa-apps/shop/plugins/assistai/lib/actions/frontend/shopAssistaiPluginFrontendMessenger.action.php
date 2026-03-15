<?php


class shopAssistaiPluginFrontendMessengerAction extends waViewAction
{

    public function execute()
    {

        $app_config = wa()->getConfig()->getAppConfig('shop');
        $path = $app_config->getAppPath('plugins/assistai/templates/frontend/Messenger.html');
        $this->setTemplate($path);




        $this->view->assign('settings', $this->params['settings']);


    }

}