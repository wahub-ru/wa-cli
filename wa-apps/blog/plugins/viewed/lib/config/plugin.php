<?php

return array(
    'name' => 'Количество просмотров',
    'description' => 'Ведет учет количество просмотров записи блога',
    'vendor' => '985310',
    'version' => '1.0.0',
    'img' => 'img/viewed.png',
    'settings' => array(
        'status' => array(
            'title' => 'Статус плагина',
            'description' => '{$post.viewed} - поле, в котором хранится количество просмотров',
            'value' => '1',
            'settings_html_function' => 'checkbox',
        ),
    ),
    'handlers' => array(
        'frontend_post' => 'frontendPost',
    ),
);
