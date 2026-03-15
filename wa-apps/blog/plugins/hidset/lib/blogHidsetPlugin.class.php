<?php

class blogHidsetPlugin extends blogPlugin
{

    public $hsets = array(
        'comments_per_page' => array(
            'type' => 'int',
            'desc' => 'Количество комментариев на одной странице/одной загрузке lazyloading на странице поста в бекэнде'
        ),
        'posts_per_page' => array(
            'type' => 'int',
            'desc' => 'Количество постов на одной странице/одной загрузке lazyloading в бекэнде'
        ),
        'cache_time' => array(
            'type' => 'int',
            'desc' => 'Время кэширования (в секундах)'
        ),
        'can_use_smarty' => array(
            'type' => 'select',
            'desc' => 'Возможность использовать Smarty в постах блога',
            'value' => array(
                'true',
                'false'
            )
        ),
    );
}
