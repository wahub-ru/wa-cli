<?php
// Декларация ключей настроек плагина (штатная совместимость Webasyst)
return array(

    'enabled' => array(
        'value'        => 1,
        'title'        => 'Включить',
        'description'  => 'Вкл/выкл подключение формы на витрине',
        'control_type' => 'checkbox',
    ),

    'trigger_class' => array(
        'value'       => 'callrequest-open',
        'title'       => 'CSS-класс триггера',
        'description' => 'По клику на элемент с этим классом открывается форма',
    ),

    'email_to' => array(
        'value'       => '',
        'title'       => 'Email для заявок',
        'description' => 'Куда отправлять уведомления (необязательно)',
    ),

    'policy_enabled' => array(
        'value'        => 0,
        'title'        => 'Показывать галочку согласия',
        'control_type' => 'checkbox',
    ),

    'policy_html' => array(
        'value'       => '',
        'title'       => 'Текст политики (HTML)',
        'description' => 'Поддерживается HTML-разметка',
    ),

    'btn_color' => array(
        'value'       => '#2ecc71',
        'title'       => 'Цвет кнопки',
        'description' => 'Основной цвет кнопки отправки',
    ),

    'btn_text_color' => array(
        'value'       => '#ffffff',
        'title'       => 'Цвет текста кнопки',
        'description' => 'Цвет текста на кнопке отправки',
    ),

    'success_text' => array(
        'value'       => 'Спасибо! Мы свяжемся с вами.',
        'title'       => 'Текст успешной отправки',
        'description' => 'Сообщение после успешной отправки формы',
    ),

    'phone_mask' => array(
        'value'       => '+7 (999) 999-99-99',
        'title'       => 'Маска телефона',
        'description' => 'Маска ввода номера телефона',
    ),

    // Резерв под конструктор дополнительных полей
    'extra_fields' => array(
        'value'       => '[]',
        'title'       => 'Дополнительные поля (JSON)',
        'description' => 'Формат: [{"name":"Комментарий","type":"textarea","required":0}]',
    ),
);
