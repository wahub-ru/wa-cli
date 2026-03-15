<?php

class shopCallrequestPluginBackendSettingsSaveController extends waController
{
    // стандартная CSRF-проверка сработает через {$wa->csrf()} в форме

    public function execute()
    {
        if (waRequest::method() !== 'post') {
            wa()->getResponse()->redirect(wa()->getAppUrl('shop').'?plugin=callrequest&action=settings');
            return;
        }

        $plugin = wa('shop')->getPlugin('callrequest');

        $data = array(
            'enabled'        => waRequest::post('enabled', 0, waRequest::TYPE_INT) ? 1 : 0,
            'trigger_class'  => (string) waRequest::post('trigger_class', 'callrequest-open', waRequest::TYPE_STRING_TRIM),
            'email_to'       => (string) waRequest::post('email_to', '', waRequest::TYPE_STRING_TRIM),
            'policy_enabled' => waRequest::post('policy_enabled', 0, waRequest::TYPE_INT) ? 1 : 0,
            // HTML не трогаем
            'policy_html'    => (string) waRequest::post('policy_html', '', waRequest::TYPE_STRING),
        );

        $plugin->saveSettings($data);

        // PRG: вернёмся на страницу настроек с флажком «сохранено»
        wa()->getResponse()->redirect(wa()->getAppUrl('shop').'?plugin=callrequest&action=settings&saved=1');
    }
}
