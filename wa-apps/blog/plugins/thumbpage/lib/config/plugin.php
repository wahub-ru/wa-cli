<?php

return array(
    'name' => 'Изображение для страницы',
    'description' => 'Позволяет добавить главное изображение страницы',
    'vendor' => 1046725,
    'version' => '1.0',
    'img' => 'img/logo_16x16.png',
    'custom_settings' => true,
    'handlers' => array(
        'page_edit' => 'pageEdit',
    ),
);
