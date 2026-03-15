<?php

return array(
    'blog_dzen_post' => array(
        'post_id'               => array('int', 11, 'null' => 0),
        'publication_mode'      => array('varchar', 32, 'null' => 0, 'default' => ''),
        'publication_format'    => array('varchar', 32, 'null' => 0, 'default' => ''),
        'indexing'              => array('varchar', 16, 'null' => 0, 'default' => 'index'),
        'comments'              => array('varchar', 32, 'null' => 0, 'default' => 'comment-all'),
        'guid'                  => array('varchar', 64, 'null' => 0, 'default' => ''),
        'pdalink'               => array('varchar', 255, 'null' => 0, 'default' => ''),
        'description'           => array('text'),
        'authors'               => array('varchar', 255, 'null' => 0, 'default' => ''),
        'author_contact_id'     => array('int', 11, 'null' => 0, 'default' => 0),
        'enclosure_url'         => array('text'),
        'media_content_url'     => array('text'),
        'media_thumbnail_url'   => array('text'),
        'media_rating'          => array('varchar', 32, 'null' => 0, 'default' => 'nonadult'),
        'content_category'      => array('varchar', 255, 'null' => 0, 'default' => ''),
        ':keys' => array(
            'PRIMARY' => 'post_id',
            'author_contact_id' => 'author_contact_id',
        ),
    ),
);
