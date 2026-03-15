<?php

return array(
    'shop_pagevisitorcounter' => array(
        'id' => array('int', 11, 'null' => 0, 'autoincrement' => 1),
        'page_id' => array('int', 11, 'null' => 0),
        'visitor_hash' => array('varchar', 32, 'null' => 0),
        'date' => array('date', 'null' => 0),
        'views' => array('int', 11, 'null' => 0, 'default' => 1),
        ':keys' => array(
            'PRIMARY' => 'id',
            'page_hash_date' => array('page_id', 'visitor_hash', 'date', 'unique' => 1),
            'date' => 'date',
            'page_id' => 'page_id',
        ),
        'comment' => 'Статистика посещений страниц',
    ),
);