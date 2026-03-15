<?php
return array(
    'shop_bnpcomments' => array(
        'id' => array('int', 11, 'null' => 0, 'autoincrement' => 1),
        'order_id' => array('int', 11, 'null' => 0),
        'datetime' => array('datetime'),
        'contact_id' => array('int', 11, 'null' => 0),
        'text' => array('text'),
        ':keys' => array(
            'PRIMARY' => 'id',
        ),
    ),
);
