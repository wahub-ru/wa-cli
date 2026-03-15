<?php
return array(
    'shop_parsing_plugin_sitemap' => array(
        'id' => array('int', 10, 'unsigned' => 1, 'null' => 0, 'autoincrement' => 1),
        'datetime' => array('timestamp', 'null' => 0, 'default' => 'CURRENT_TIMESTAMP'),
        'url' => array('varchar', 255, 'null' => 0, 'default' => ''),
        'product_id' => array('int', 10, 'unsigned' => 1),
        'status' => array('tinyint', 1, 'unsigned' => 1, 'null' => 0, 'default' => 0),
        'profile_id' => array('int', 10, 'unsigned' => 1, 'null' => 0, 'default' => 0),
        'parsing' => array('tinyint', 1, 'unsigned' => 1, 'null' => 0, 'default' => 0),
        ':keys' => array(
            'PRIMARY' => 'id',
            'product_id' => 'product_id',
            'parsing' => 'parsing',
            'profile_id' => 'profile_id',
            'url' => array('url', 'unique' => 1),
        ),
    ),
);
