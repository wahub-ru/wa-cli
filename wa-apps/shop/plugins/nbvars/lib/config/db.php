<?php
return array(
    'shop_nbvars_variable' => array(
        'id' => array('int', 11, 'null' => 0, 'autoincrement' => 1),
        'name' => array('varchar', 255, 'null' => 0),
        'value' => array('text'),
        ':keys' => array(
            'PRIMARY' => 'id',
        ),
    ),
);
