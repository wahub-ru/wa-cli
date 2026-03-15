<?php

class shopCallrequestPluginBackendSettingsAction extends waViewAction
{
    public function execute()
    {
        // POST -> save -> PRG
        if (waRequest::method() === 'post') {
            $this->save();
            return;
        }

        // без лэйаута! (иначе "админка в админке")
        $defaults = array(
            'enabled'        => 1,
            'trigger_class'  => 'callrequest-open',
            'email_to'       => '',
            'policy_enabled' => 0,
            'policy_html'    => ''
        );

        $settings = $this->loadSettings($defaults);

        $this->view->assign(array(
            's'     => $settings,
            'saved' => waRequest::get('saved', 0, waRequest::TYPE_INT),
        ));

        // используем ваш реальный шаблон
        $tpl = wa()->getAppPath('plugins/callrequest/templates/actions/settings/Settings.html', 'shop');
        $this->setTemplate($tpl);
    }

    private function loadSettings(array $defaults)
    {
        $m   = new waAppSettingsModel();
        $app = 'shop';
        $res = $defaults;

        foreach ($defaults as $k => $def) {
            $v = $m->get($app, 'plugins.callrequest.'.$k, null);
            if ($v === null) {
                $v = $m->get($app, 'plugin.callrequest.'.$k, $def);
            }
            $res[$k] = $v;
        }
        return $res;
    }

    private function save()
    {
        waLog::log([
            'HIT' => 'SettingsSaveController',
            'FILE' => __FILE__,
            'post' => waRequest::post()
        ], 'callrequest_backend_debug.log');

        // CSRF в шаблоне через {$wa->csrf()}
        $enabled        = waRequest::post('enabled', 0, waRequest::TYPE_INT) ? 1 : 0;
        $trigger_class  = (string) waRequest::post('trigger_class', 'callrequest-open', waRequest::TYPE_STRING_TRIM);
        $email_to       = (string) waRequest::post('email_to', '', waRequest::TYPE_STRING_TRIM);
        $policy_enabled = waRequest::post('policy_enabled', 0, waRequest::TYPE_INT) ? 1 : 0;
        $policy_html    = (string) waRequest::post('policy_html', '', waRequest::TYPE_STRING); // HTML как есть

        $m   = new waAppSettingsModel();
        $app = 'shop';
        foreach (array('plugins.callrequest.', 'plugin.callrequest.') as $pfx) {
            $m->set($app, $pfx.'enabled',        $enabled);
            $m->set($app, $pfx.'trigger_class',  $trigger_class);
            $m->set($app, $pfx.'email_to',       $email_to);
            $m->set($app, $pfx.'policy_enabled', $policy_enabled);
            $m->set($app, $pfx.'policy_html',    $policy_html);
        }

        // Если пришли из списка плагинов — вернёмся туда же (с хэшем)
        $ref = (string) waRequest::server('HTTP_REFERER', '');
        if ($ref && strpos($ref, 'action=plugins') !== false) {
            wa()->getResponse()->redirect($ref);
            return;
        }

        // иначе остаёмся на своей странице настроек
        wa()->getResponse()->redirect(wa()->getAppUrl('shop').'?plugin=callrequest&action=settings&saved=1');
        waLog::log([
            'HIT' => 'SettingsSaveController',
            'FILE' => __FILE__,
            'post' => waRequest::post()
        ], 'callrequest_backend_debug.log');

    }
}
