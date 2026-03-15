<?php

return array(

    'active' => array(
        'value' => "1",
        'title' => "Активность плагина",
        'control_type' => waHtmlControl::SELECT,
        'options' => array(
            array(
                "value" => "1",
                "title" => "Включен",
            ),
            array(
                "value" => "0",
                "title" => "Отключен",
            ),
        ),
    ),

    'menu_title' => array(
        'value' => "Заказать звонок",
        'title' => "Заголовок в меню административной панели",
        'control_type' => waHtmlControl::INPUT,
    ),

    'menu_counter' => array(
        'value' => "1",
        'title' => "Выводить количество новых заявок в пункт меню",
        'control_type' => waHtmlControl::SELECT,
        'options' => array(
            array(
                "value" => "1",
                "title" => "Включен",
            ),
            array(
                "value" => "0",
                "title" => "Отключен",
            ),
        )
    ),

    'request_pagination' => array(
        'value' => 30,
        'title' => "Количество записей страницы",
        'control_type' => waHtmlControl::INPUT,
    ),

    'search_region' => array(
        'value' => "1",
        'title' => "Определять регион по ip",
        'control_type' => waHtmlControl::SELECT,
        'options' => array(
            array(
                "value" => "1",
                "title" => "Да",
            ),
            array(
                "value" => "0",
                "title" => "Нет",
            ),
        )
    ),

    'minimization' => array(
        'value' => "1",
        'title' => "Подключать минимизированные скрипты",
        'control_type' => waHtmlControl::SELECT,
        'options' => array(
            array(
                "value" => "1",
                "title" => "Да",
            ),
            array(
                "value" => "0",
                "title" => "Нет",
            ),
        ),
    ),

);
