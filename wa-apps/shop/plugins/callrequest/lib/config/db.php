<?php
return array(

    'shop_callrequest_requests' => array(
        'id'               => array('int', 11, 'null' => 0, 'autoincrement' => 1),
        'create_datetime'  => array('datetime', 'null' => 0),
        'name'             => array('varchar', 255, 'null' => 0),
        'phone'            => array('varchar', 64,  'null' => 0),
        'email'            => array('varchar', 255, 'null' => 1, 'default' => null),
        'policy'           => array('int', 11, 'null' => 0, 'default' => 0),
        'fields_json'      => array('text', 'null' => 1),
        'ip'               => array('varchar', 45,   'null' => 1, 'default' => null),
        'user_agent'       => array('varchar', 255,  'null' => 1, 'default' => null),
        'referer'          => array('varchar', 1024, 'null' => 1, 'default' => null),
        'status'           => array('varchar', 16,   'null' => 0, 'default' => 'new'),
        'manager_comment'  => array('text', 'null' => 1),

        ':keys' => array(
            'PRIMARY'             => 'id',
            'idx_create_datetime' => 'create_datetime',
            'idx_status'          => 'status',
        ),
    ),

    // 🔽 ВАША ТАБЛИЦА ЦЕН
    'shop_callrequest_prices' => array(
        'id' => array(
            'int', 11,
            'null' => 0,
            'autoincrement' => 1
        ),

        'type' => array(
            'varchar', 255,
            'null' => 0
        ),

        'size' => array(
            'varchar', 50,
            'null' => 0
        ),

        'thickness' => array(
            'varchar', 50,
            'null' => 0
        ),

        'print' => array(
            'varchar', 50,
            'null' => 0
        ),

        'qty' => array(
            'int', 11,
            'null' => 0
        ),

        'price' => array(
            'decimal', '10,2',
            'null' => 0
        ),

        ':keys' => array(
            'PRIMARY' => 'id',
            'idx_type' => 'type',
            'idx_size' => 'size',
        ),
    ),

);