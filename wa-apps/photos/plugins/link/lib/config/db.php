<?php
return array(
    'photos_link' => array(
        'photo_id' => array('int', 11, 'null' => 0),
        'url' => array('varchar', 255),
        'target' => array('varchar', 10),
        ':keys' => array(
            'PRIMARY' => 'photo_id',
        ),
    ),
);
