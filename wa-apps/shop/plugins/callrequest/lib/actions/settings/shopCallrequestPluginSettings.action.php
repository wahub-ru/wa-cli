<?php

class shopCallrequestPluginSettingsAction extends waViewAction
{
    public function execute()
    {
        $m   = new waAppSettingsModel();
        $app = 'shop';

        $get = function ($key, $default = null) use ($m, $app) {
            $v = $m->get($app, 'plugins.callrequest.'.$key, null);
            if ($v === null) {
                $v = $m->get($app, 'plugin.callrequest.'.$key, null);
            }
            return $v === null ? $default : $v;
        };

        $price_model = new shopCallrequestPriceModel();

        $this->view->assign([
            'enabled'        => (int)$get('enabled', 1),
            'trigger_class'  => (string)$get('trigger_class', 'callrequest-open'),
            'email_to'       => (string)$get('email_to', ''),
            'policy_enabled' => (int)$get('policy_enabled', 0),
            'policy_html'    => (string)$get('policy_html', ''),
            'btn_color'      => (string)$get('btn_color', '#2ecc71'),
            'btn_text_color' => (string)$get('btn_text_color', '#ffffff'),
            'success_text'   => (string)$get('success_text', ''),
            'phone_mask'     => (string)$get('phone_mask', '+7 (999) 999-99-99'),
            'prices'         => $price_model->getAll(),
        ]);
    }
}
