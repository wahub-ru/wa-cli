<?php
return [
    'name'        => 'Онлайн-консультант (Telegram)',
    'description' => 'Плавающий чат на сайте + ответы менеджера из Telegram',
    'vendor'      => '1200255',
    'version'     => '2.0.0',
    'img'        => 'img/plugin.png',
    'custom_settings' => true,
    'shop_settings' => true,
    'frontend'    => true,
    'handlers'    => [
        'frontend_head'   => 'frontendAssets',
        'backend_menu'  => 'backendMenu',
   	    'backend_extended_menu'=> 'backendExtendedMenu', 
        'backend_customer' => 'backendCustomer',
    ],
];
