<?php

return array(
    'bot_patterns' => array(
        'value' => 'Googlebot|Bingbot|YandexBot|DuckDuckBot|baiduspider|sogou|exabot|facebot|ia_archiver',
        'title' => 'Регулярное выражение для определения ботов',
        'control_type' => waHtmlControl::TEXTAREA,
        'description' => 'Укажите регулярное выражение для идентификации ботов по User-Agent',
    ),
    'cache_ttl' => array(
        'value' => 600,
        'title' => 'Время жизни кеша (в секундах)',
        'control_type' => waHtmlControl::INPUT,
        'description' => 'Укажите, как долго хранить данные в кеше',
    ),
    'track_products' => array(
        'value' => 1,
        'title' => 'Отслеживать просмотры товаров',
        'control_type' => waHtmlControl::CHECKBOX,
        'description' => 'Включить отслеживание просмотров страниц товаров',
    ),
    'track_categories' => array(
        'value' => 1,
        'title' => 'Отслеживать просмотры категорий',
        'control_type' => waHtmlControl::CHECKBOX,
        'description' => 'Включить отслеживание просмотров страниц категорий',
    ),
    'track_pages' => array(
        'value' => 1,
        'title' => 'Отслеживать просмотры страниц',
        'control_type' => waHtmlControl::CHECKBOX,
        'description' => 'Включить отслеживание просмотров статических страниц',
    ),
);