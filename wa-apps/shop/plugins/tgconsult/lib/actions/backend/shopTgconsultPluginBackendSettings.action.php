<?php
class shopTgconsultPluginBackendSettingsAction extends waViewAction
{
    public function execute()
    {
        $plugin = wa('shop')->getPlugin('tgconsult');
        $s = $plugin->getSettings();

        if (waRequest::method() === 'post') {
            wa()->getResponse()->setHeader('Content-type', 'application/json');
            wa('shop')->getConfig()->getApp(); // touch
            $data = [
                'enabled'      => (int)waRequest::post('enabled', 0, waRequest::TYPE_INT),
                'bot_token'    => (string)waRequest::post('bot_token', '', waRequest::TYPE_STRING_TRIM),
                'welcome'      => (string)waRequest::post('welcome', '', waRequest::TYPE_STRING_TRIM),
                'manager_name' => (string)waRequest::post('manager_name', '', waRequest::TYPE_STRING_TRIM),
                'manager_photo'=> (string)waRequest::post('manager_photo', '', waRequest::TYPE_STRING_TRIM),
            ];
            $plugin->saveSettings($data);
            echo json_encode(['status'=>'ok']); return;
        }

        $this->view->assign('s', $s);
        $this->setTemplate(wa()->getAppPath('plugins/tgconsult/templates/BackendSettings.html', 'shop'));
    }
}
