<?php

return array(
    'shop_personalsale_plugin' => array(
        'id' => array('int', 11, 'null' => 0, 'autoincrement' => 1),
        'contact_id' => array('int', 11, 'null' => 0, 'default' => '0'),
        'percent' => array('float', 11, 'null' => 0, 'default' => '0'),
		':keys' => array(
            'PRIMARY' => 'id',
        ),
    ),
);
