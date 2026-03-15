<?php

return array(
    'name'        => 'Дзен для медиа',
    'description' => 'RSS-выгрузка публикаций блога в формате Яндекс Дзен',
    'vendor'      => '1036600',
    'version'     => '1.3.1',
    'img'         => 'img/icon-48.png',
    'frontend'    => true,
    'handlers'    => array(
        'backend_post_edit' => 'backendPostEdit',
        'post_save'         => 'postSave',
        'post_publish'      => 'postSave',
        'post_delete'       => 'postDelete',
        'post_shedule'      => 'postSave',
        'routing'           => 'routing',
    ),
    'custom_settings' => true,
);
