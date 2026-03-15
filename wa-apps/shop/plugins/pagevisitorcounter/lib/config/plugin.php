<?php

return array(
    'name' => 'PageVisitorCounter', // Имя плагина
    'version' => '1.0.0',
    'vendor' => 'your_vendor_id',   // Идентификатор разработчика
    'description' => 'Плагин для подсчета уникальных посетителей страниц магазина.',
    'img' => 'img/plugin.png',
    'frontend' => true, // Разрешить обработку фронтенд-запросов

    'handlers' => array(
        // Регистрируем хук для вставки JS-кода на страницы магазина
        'frontend_head' => 'frontendHead',
        // Можно добавить другие хуки, например, для админки:
        // 'backend_product' => 'backendProductEdit'
    ),
);