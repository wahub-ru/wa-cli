<?php
return array(
    'shop_uniparams_lists' => array(
        'id' => array('int', 11, 'unsigned' => 1, 'null' => 0, 'autoincrement' => 1),
        'name' => array('varchar', 255, 'null' => 0),
        'front_index' => array('int', 11, 'null' => 0),
        'key_name' => array('text', 'null' => 0),
        'description' => array('text', 'null' => 1),
        ':keys' => array(
            'PRIMARY' => 'id',
        ),
    ),
    'shop_uniparams_lists_params' => array(
        'id' => array('int', 11, 'unsigned' => 1, 'null' => 0, 'autoincrement' => 1),
        'list_id' => array('int', 11, 'unsigned' => 1, 'null' => 0),
        'key_name' => array('text', 'null' => 1),
        'type' => array('text', 'null' => 1),
        'content' => array('text', 'null' => 1),
        ':keys' => array(
            'PRIMARY' => 'id',
        ),
    ),
    'shop_uniparams_lists_fields' => array(
        'id' => array('int', 11, 'unsigned' => 1, 'null' => 0, 'autoincrement' => 1),
        'list_id' => array('int', 11, 'unsigned' => 1, 'null' => 0),
        'name' => array('varchar', 255, 'null' => 0),
        'keyname' => array('varchar', 255, 'null' => 0),
        'type' => array('text', 'null' => 0),
        'description' => array('text', 'null' => 1),
        ':keys' => array(
            'PRIMARY' => 'id',
        ),
    ),
    'shop_uniparams_items' => array(
        'id' => array('int', 11, 'unsigned' => 1, 'null' => 0, 'autoincrement' => 1),
        'list_id' => array('int', 11, 'unsigned' => 1, 'null' => 0),
        'enabled' => array('int', 11, 'null' => 0, 'default' => 1),
        'front_index' => array('int', 11, 'null' => 0),
        ':keys' => array(
            'PRIMARY' => 'id',
        ),
    ),
    'shop_uniparams_items_vals' => array(
        'id' => array('int', 11, 'unsigned' => 1, 'null' => 0, 'autoincrement' => 1),
        'field_id' => array('int', 11, 'unsigned' => 1, 'null' => 0),
        'item_id' => array('int', 11, 'unsigned' => 1, 'null' => 0),
        'content' => array('text', 'null' => 0),
        ':keys' => array(
            'PRIMARY' => 'id',
        ),
    ),
);
