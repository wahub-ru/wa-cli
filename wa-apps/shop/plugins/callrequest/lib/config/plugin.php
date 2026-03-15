<?php
return array(
    'name'        => 'Калькулятор пакетов',
    'description' => 'Квиз форма с уведомлением на почту',
    'version'     => '2026.1.03',
    'vendor'      => '1200255',
    'img'         => 'img/callrequest.png',
    'class'       => 'shopCallrequestPlugin',
    'custom_settings' => true,
    'frontend'    => true,
    'shop_settings' => true,
    'handlers' => array(
        'frontend_head'         => 'frontendHead',
        'backend_extended_menu' => 'backendExtendedMenu',
        'routing'               => 'routingHandler',
        'backend_menu'          => 'backendMenu'
    )
);
