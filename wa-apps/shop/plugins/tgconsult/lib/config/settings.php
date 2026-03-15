<?php
return [
    'enabled' => [
        'value'        => 0,
        'title'        => /*_wp*/('Включить плагин'),
        'description'  => /*_wp*/('Показывать виджет на сайте и принимать сообщения'),
        'control_type' => waHtmlControl::CHECKBOX,
    ],
    'bot_token' => [
        'value'        => '',
        'title'        => /*_wp*/('Токен Telegram-бота'),
        'description'  => '',
        'control_type' => waHtmlControl::INPUT,
        'class'        => 'long',
    ],
    'manager_chat_id' => [
        'value'        => '',
        'title'        => /*_wp*/('ID чата менеджера'),
        'description'  => /*_wp*/('Личный, групповой или канал (может быть отрицательным, например -1001234567890)'),
        'control_type' => waHtmlControl::INPUT,
        'class'        => 'long',
    ],

    'welcome' => [
        'value'        => 'Здравствуйте! Чем помочь?',
        'title'        => /*_wp*/('Приветственное сообщение'),
        'control_type' => waHtmlControl::INPUT,
    ],
    'manager_name' => [
        'value'        => 'Менеджер',
        'title'        => /*_wp*/('Имя менеджера'),
        'control_type' => waHtmlControl::INPUT,
    ],
    'manager_name_mode' => [
        'value'        => 'settings',
        'title'        => /*_wp*/('Имя отправителя в ответах'),
        'control_type' => waHtmlControl::INPUT,
    ],
    'manager_photo' => [
        'value'        => '',
        'title'        => /*_wp*/('Фото менеджера (URL)'),
        'description'  => /*_wp*/('Нажмите «Загрузить фото…», чтобы загрузить файл; URL подставится автоматически.'),
        'control_type' => waHtmlControl::INPUT,
        'class'        => 'long',
    ],
    'manager_photo_delete' => [
        'value'        => 0,
        'title'        => /*_wp*/('Удалить фото менеджера'),
        'description'  => /*_wp*/('При сохранении очистит поле и удалит файл, если он загружен на сайт.'),
        'control_type' => waHtmlControl::CHECKBOX,
    ],

    'hide_button' => [
        'value'        => 0,
        'title'        => /*_wp*/('Скрыть иконку чата'),
        'control_type' => waHtmlControl::CHECKBOX,
    ],
    'icon_color' => [
        'value'        => '#0D6EFD',
        'title'        => /*_wp*/('Цвет иконки чата'),
        'description'  => /*_wp*/('Используется для круглой кнопки-иконки виджета.'),
        'control_type' => waHtmlControl::CUSTOM,
        'custom_control' => '<input type="color" name="icon_color" value="{$value|escape}" style="height:36px;width:60px;padding:0;border:none;background:transparent;">',
    ],
    'widget_position' => [
        'value'        => 'right',
        'title'        => /*_wp*/('Позиция кнопки чата'),
        'control_type' => waHtmlControl::INPUT,
    ],
    'widget_offset_side' => [
        'value'        => 22,
        'title'        => /*_wp*/('Отступ от края'),
        'control_type' => waHtmlControl::INPUT,
    ],
    'widget_offset_bottom' => [
        'value'        => 70,
        'title'        => /*_wp*/('Отступ снизу'),
        'control_type' => waHtmlControl::INPUT,
    ],

    'working_hours_enabled' => [
        'value'        => 0,
        'title'        => /*_wp*/('Использовать график работы'),
        'control_type' => waHtmlControl::CHECKBOX,
    ],
    'working_timezone' => [
        'value'        => date_default_timezone_get() ?: 'UTC',
        'title'        => /*_wp*/('Часовой пояс графика'),
        'control_type' => waHtmlControl::INPUT,
    ],
    'offhours_autoreply' => [
        'value'        => 'Сейчас мы вне графика работы. Оставьте, пожалуйста, ваши контакты для связи, и мы ответим в рабочее время.',
        'title'        => /*_wp*/('Текст автоответа вне графика'),
        'control_type' => waHtmlControl::INPUT,
    ],

    'work_mon_enabled' => ['value' => 1, 'control_type' => waHtmlControl::CHECKBOX],
    'work_mon_start'   => ['value' => '09:00', 'control_type' => waHtmlControl::INPUT],
    'work_mon_end'     => ['value' => '18:00', 'control_type' => waHtmlControl::INPUT],
    'work_tue_enabled' => ['value' => 1, 'control_type' => waHtmlControl::CHECKBOX],
    'work_tue_start'   => ['value' => '09:00', 'control_type' => waHtmlControl::INPUT],
    'work_tue_end'     => ['value' => '18:00', 'control_type' => waHtmlControl::INPUT],
    'work_wed_enabled' => ['value' => 1, 'control_type' => waHtmlControl::CHECKBOX],
    'work_wed_start'   => ['value' => '09:00', 'control_type' => waHtmlControl::INPUT],
    'work_wed_end'     => ['value' => '18:00', 'control_type' => waHtmlControl::INPUT],
    'work_thu_enabled' => ['value' => 1, 'control_type' => waHtmlControl::CHECKBOX],
    'work_thu_start'   => ['value' => '09:00', 'control_type' => waHtmlControl::INPUT],
    'work_thu_end'     => ['value' => '18:00', 'control_type' => waHtmlControl::INPUT],
    'work_fri_enabled' => ['value' => 1, 'control_type' => waHtmlControl::CHECKBOX],
    'work_fri_start'   => ['value' => '09:00', 'control_type' => waHtmlControl::INPUT],
    'work_fri_end'     => ['value' => '18:00', 'control_type' => waHtmlControl::INPUT],
    'work_sat_enabled' => ['value' => 0, 'control_type' => waHtmlControl::CHECKBOX],
    'work_sat_start'   => ['value' => '10:00', 'control_type' => waHtmlControl::INPUT],
    'work_sat_end'     => ['value' => '16:00', 'control_type' => waHtmlControl::INPUT],
    'work_sun_enabled' => ['value' => 0, 'control_type' => waHtmlControl::CHECKBOX],
    'work_sun_start'   => ['value' => '10:00', 'control_type' => waHtmlControl::INPUT],
    'work_sun_end'     => ['value' => '16:00', 'control_type' => waHtmlControl::INPUT],

    'webhook_url' => [
        'value'        => '',
        'title'        => /*_wp*/('Webhook URL (необязательно)'),
        'description'  => /*_wp*/('Если пусто — будет использован авто-URL вида https://домен/tgconsult/webhook/'),
        'control_type' => waHtmlControl::INPUT,
        'class'        => 'long',
    ],
];
