<?php
return array(

    'shop_skcallback_defines' => array(
        'id' => array('int', 11, 'null' => 0, 'autoincrement' => 1),
        'name' => array('varchar', 64, 'null' => 0),
        'value' => array('text'),
        ':keys' => array(
            'PRIMARY' => 'id',
            'name' => array('name', 'unique' => 1),
        ),
    ),

    'shop_skcallback_controls_type' => array(
        'id' => array('int', 11, 'null' => 0, 'autoincrement' => 1),
        'name' => array('varchar', 32, 'null' => 0),
        'title' => array('varchar', 128, 'null' => 0),
        'placeholder' => array('varchar', 255),
        'is_require' => array('tinyint', 4, 'default' => 0),
        'is_additional' => array('tinyint', 4, 'default' => 0),
        ':keys' => array(
            'PRIMARY' => 'id'
        ),
    ),

    'shop_skcallback_controls' => array(
        'id' => array('int', 11, 'null' => 0, 'autoincrement' => 1),
        'type_id' => array('int', 11, 'null' => 0),
        'title' => array('varchar', 128),
        'additional' => array('varchar', 255),
        'require' => array('tinyint', 4, 'default' => 0),
        'sort' => array('int', 11, 'default' => 0),
        ':keys' => array(
            'PRIMARY' => 'id',
            'type_id' => array('type_id'),
        ),
    ),

    'shop_skcallback_requests' => array(
        'id' => array('int', 11, 'null' => 0, 'autoincrement' => 1),
        'status_id' => array('int', 'default' => 0),
        'customer_id' => array('int', 'default' => 0),
        'date' => array('datetime', 'null' => 0),
        'referrer' => array('text'),
        'region' => array('varchar', 255),
        'city' => array('varchar', 255),
        'ip' => array('varchar', 64, 'default' => ''),
        ':keys' => array(
            'PRIMARY' => 'id',
            'status_id' => array('status_id')
        ),
    ),

    'shop_skcallback_values' => array(
        'id' => array('int', 11, 'null' => 0, 'autoincrement' => 1),
        'request_id' => array('int', 11, 'null' => 0),
        'control_id' => array('int', 11, 'null' => 0),
        'value' => array('text'),
        ':keys' => array(
            'PRIMARY' => 'id',
            'request_id' => array('request_id'),
            'control_id' => array('control_id'),
        ),
    ),

    'shop_skcallback_cart' => array(
        'id' => array('int', 11, 'null' => 0, 'autoincrement' => 1),
        'request_id' => array('int', 11, 'null' => 0),
        'product_id' => array('int', 11, 'null' => 0),
        'sku_id' => array('int', 11, 'null' => 0),
        'quantity' => array('int', 11, 'null' => 0),
        ':keys' => array(
            'PRIMARY' => 'id',
            'request_id' => array('request_id'),
            'product_id' => array('product_id'),
            'sku_id' => array('sku_id'),
        ),
    ),

    'shop_skcallback_status' => array(
        'id' => array('int', 11, 'null' => 0, 'autoincrement' => 1),
        'title' => array('varchar', 255, 'null' => 0),
        'color' => array('varchar', 32),
        'starter' => array('tinyint', 4, 'default' => 0),
        ':keys' => array(
            'PRIMARY' => 'id',
        ),
    ),

);