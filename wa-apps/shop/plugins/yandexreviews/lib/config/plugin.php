<?php
return [
    'name'         => 'Отзывы с Яндекс Карт',
    'description'  => 'Импорт и вывод отзывов Яндекс.Карт',
    'img'          => 'img/yandexreviews.png',
    'vendor'       => '1200255',
    'version'      => '2026.02.4',
    'frontend'     => true,
    'shop_settings'=> true,

    // дефолтные значения (сохраняются через стандартный action=save)
    'settings' => [
        'enabled'            => ['value' => 1],
        'company_url'        => ['value' => ''],
        'view_mode'          => ['value' => 'tiles'],
        'initial_limit'      => ['value' => 8],
        'hide_low_ratings'   => ['value' => 0],
        'show_review_button' => ['value' => 1],

        // Кастомизация кнопки «Оставить отзыв»
        'review_button_text'            => ['value' => 'Оставить отзыв на Яндекс Картах'],
        'review_button_bg_color'        => ['value' => ''],   // пусто = использовать стили темы/плагина
        'review_button_text_color'      => ['value' => ''],   // пусто = использовать стили темы/плагина
        'review_button_bg_color_hover'  => ['value' => ''],   // пусто = не переопределять hover
        'review_button_text_color_hover'=> ['value' => ''],   // пусто = не переопределять hover
        'review_button_radius'          => ['value' => ''],   // пусто = использовать стили темы/плагина

        'cron_batch_limit'   => ['value' => 30],
    ],

    'handlers' => [
        'frontend_head' => 'frontendHead',
        'frontend_page' => 'frontendPage',
    ],
];
