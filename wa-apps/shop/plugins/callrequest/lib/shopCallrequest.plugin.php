<?php

class shopCallrequestPlugin extends shopPlugin
{
    /**
     * Совместимая выборка настроек (оба префикса).
     */
    private function gs($key, $default = null)
    {
        $m   = new waAppSettingsModel();
        $app = 'shop';
        $v   = $m->get($app, 'plugins.callrequest.'.$key, null);
        if ($v === null) {
            $v = $m->get($app, 'plugin.callrequest.'.$key, null);
        }
        return ($v === null) ? $default : $v;
    }

    /**
     * Подключаем настройки, CSS и JS на витрину.
     */
public function frontendHead()
{
    $m   = new waAppSettingsModel();
    $app = 'shop';
    $get = function($k, $def=null) use($m,$app){
        $v = $m->get($app,'plugins.callrequest.'.$k,null);
        if ($v === null) $v = $m->get($app,'plugin.callrequest.'.$k,$def);
        return $v;
    };

    if ((int)$get('enabled',1)!==1) return '';

    // цвета с очисткой
    $btn_bg  = (string)$get('btn_color','#2ecc71');
    $btn_txt = (string)$get('btn_text_color','#ffffff'); // НОВОЕ
    $btn_bg  = preg_replace('~[^#0-9a-fA-F]~','',$btn_bg) ?: '#2ecc71';
    $btn_txt = preg_replace('~[^#0-9a-fA-F]~','',$btn_txt) ?: '#ffffff';

    $settings = array(
        'trigger_class'  => (string)$get('trigger_class','callrequest-open'),
        'policy_enabled' => (int)$get('policy_enabled',0),
        'policy_html'    => (string)$get('policy_html',''),
        'btn_color'      => $btn_bg,
        'btn_text_color' => $btn_txt, // НОВОЕ
        'success_text'   => (string)$get('success_text','Спасибо! Мы свяжемся с вами.'),
        'phone_mask'     => (string)$get('phone_mask','+7 (999) 999-99-99'),
        'post_url'       => wa()->getRootUrl().'callrequest/',
    );

    $static = wa()->getAppStaticUrl('shop');
    $ver    = wa()->getVersion();

    $html  = '<script>window.CallRequestSettings='.json_encode($settings, JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES).';</script>';
    $html .= '<link rel="stylesheet" href="'.$static.'plugins/callrequest/css/callrequest.css?v='.$ver.'">';
    $html .= '<script src="'.$static.'plugins/callrequest/js/callrequest.js?v='.$ver.'"></script>';

    // применим цвета к кнопке
    $html .= '<style>.cr-modal .cr-submit{background:'.$btn_bg.';border-color:'.$btn_bg.';color:'.$btn_txt.' !important}</style>';

    return $html;
}




    /**
     * Webasyst 2 — пункт в боковом меню.
     */
    public function backendExtendedMenu(&$params)
    {
        $url  = wa()->getAppUrl('shop').'?plugin=callrequest&action=requests';

        $item = array(
            'name'         => _wp('Обратные звонки'),
            'icon'         => '<i class="fas fa-phone"></i>',
            'placement'    => 'body',
            'insert_after' => 'orders',
            'url'          => $url
        );

        // информер новых
        try {
            if (class_exists('shopCallrequestPluginRequestModel')) {
                $m   = new shopCallrequestPluginRequestModel();
                $cnt = (int) $m->select('COUNT(*) AS cnt')->where("status='new'")->fetchField();
                if ($cnt > 0) {
                    $item['counter'] = $cnt;
                }
            }
        } catch (Exception $e) {}

        $params['menu']['callrequest'] = $item;
    }

    /**
     * Старый UI — пункт после 'backend_menu.core_li'.
     * Возвращаем ключ 'core_li', тогда вывод попадёт сразу после соответствующего хука
     * (см. комментарий <!-- plugin hook: 'backend_menu.core_li' --> в HTML).
     */
    public function backendMenu()
    {
        $url     = wa()->getAppUrl('shop').'?plugin=callrequest&action=requests';
        $name    = _wp('Раасчет пакетов');
        $counter = '';

        try {
            if (class_exists('shopCallrequestPluginRequestModel')) {
                $m   = new shopCallrequestPluginRequestModel();
                $cnt = (int) $m->select('COUNT(*) AS cnt')->where("status='new'")->fetchField();
                if ($cnt > 0) {
                    $counter = " <sup class='red' style='display:inline'>{$cnt}</sup>";
                }
            }
        } catch (Exception $e) {}

        // id оставляю для удобства (если когда-нибудь захочется найти/переставить)
        return array(
            'core_li' => '<li id="cr-oldui-menu" class="no-tab"><a href="'.$url.'">'.$name.$counter.'</a></li>'
        );
    }

    public function routingHandler($route)
    {
        if (wa()->getEnv() == 'frontend') {
            return [
                // этот URL будет обрабатываться твоим action
                $this->id . '/getPrices/' => [
                    'module' => 'frontend',
                    'action' => 'getPrices'
                ],
                'callrequest/send/' => [
                    'module' => 'frontend',
                    'plugin' => $this->id,
                    'action' => 'send',
                ],
            ];
        }
    }

}
