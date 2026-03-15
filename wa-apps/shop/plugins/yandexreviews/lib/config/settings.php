<?php

return [
    'enabled' => [
        'title'        => 'Включить плагин',
        'description'  => 'Включает импорт и вывод отзывов.',
        'control_type' => waHtmlControl::CHECKBOX,
        'value'        => 1,
    ],

    'company_url' => [
        'title'        => 'Ссылка на компанию на Яндекс.Картах',
        'description'  => 'Например: https://yandex.ru/maps/org/.../reviews/',
        'control_type' => waHtmlControl::INPUT,
        'value'        => '',
    ],

    'view_mode' => [
        'title'        => 'Вид отображения',
        'description'  => 'Плитки или список.',
        'control_type' => waHtmlControl::SELECT,
        'options'      => [
            'tiles' => 'Плитки',
            'list'  => 'Список',
        ],
        'value'        => 'tiles',
    ],

    'initial_limit' => [
        'title'        => 'Количество отзывов до «Показать ещё»',
        'description'  => 'Сколько отзывов показывать сразу. 1–50.',
        'control_type' => waHtmlControl::INPUT,
        'value'        => 8,
    ],

    'cron_batch_limit' => [
        'title'        => 'Сколько загружать за один запуск CRON',
        'description'  => 'Рекомендуем 20–50. 1–200.',
        'control_type' => waHtmlControl::INPUT,
        'value'        => 30,
    ],

    'hide_low_ratings' => [
        'title'        => 'Скрывать отзывы с оценкой 1–3',
        'description'  => 'При выводе на витрине отзывы с рейтингом < 4 будут скрыты.',
        'control_type' => waHtmlControl::CHECKBOX,
        'value'        => 0,
    ],

    'show_review_button' => [
        'title'        => 'Кнопка «Оставить отзыв на Яндекс Картах»',
        'description'  => 'Показывать кнопку вверху блока с отзывами.',
        'control_type' => waHtmlControl::CHECKBOX,
        'value'        => 1,
    ],

    // ===== Кастомизация кнопки =====
    'review_button_text' => [
        'title'        => 'Текст на кнопке',
        'description'  => 'Например: «Оставить отзыв».',
        'control_type' => waHtmlControl::INPUT,
        'value'        => 'Оставить отзыв на Яндекс Картах',
    ],
    'review_button_bg_color' => [
        'title'        => 'Цвет фона кнопки',
        'description'  => 'HEX-цвет, например #ffd400. Пусто — использовать стили темы/плагина.',
        'control_type' => waHtmlControl::INPUT,
        'value'        => '',
    ],
    'review_button_text_color' => [
        'title'        => 'Цвет текста кнопки',
        'description'  => 'HEX-цвет, например #000000. Пусто — использовать стили темы/плагина.',
        'control_type' => waHtmlControl::INPUT,
        'value'        => '',
    ],
    'review_button_bg_color_hover' => [
        'title'        => 'Цвет фона кнопки при наведении',
        'description'  => 'HEX-цвет, например #ffea00. Пусто — использовать стили темы/плагина.',
        'control_type' => waHtmlControl::INPUT,
        'value'        => '',
    ],
    'review_button_text_color_hover' => [
        'title'        => 'Цвет текста кнопки при наведении',
        'description'  => 'HEX-цвет, например #000000. Пусто — использовать стили темы/плагина.',
        'control_type' => waHtmlControl::INPUT,
        'value'        => '',
    ],
    'review_button_radius' => [
        'title'        => 'Скругление кнопки (border-radius)',
        'description'  => 'В пикселях. Пусто — использовать стили темы/плагина.',
        'control_type' => waHtmlControl::INPUT,
        'value'        => '',
    ],
];
