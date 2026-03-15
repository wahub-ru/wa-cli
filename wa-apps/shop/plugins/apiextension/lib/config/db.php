<?php
/**
 * DB
 *
 * @author Steemy, created by 25.08.2021
 */

return array(
    'shop_apiextension_reviews_vote' => array(
        'review_id' => array('int', 11, 'null' => 0),
        'contact_id' => array('int', 11, 'null' => 0),
        'vote_like' => array('int', 1, 'null' => 0, 'default' => '0'),
        'vote_dislike' => array('int', 1, 'null' => 0, 'default' => '0'),
        ':keys' => array(
            'shop_apiextension_reviews_vote_reviews_id_contact_id' => array('review_id', 'contact_id', 'unique' => 1),
        ),
    ),
    'shop_apiextension_reviews_affiliate' => array(
        'id' => array('int', 11, 'null' => 0, 'autoincrement' => 1),
        'contact_id' => array('int', 11, 'null' => 0),
        'review_id' => array('int', 11, 'null' => 0, 'default' => 0),
        'order_id' => array('int', 11, 'null' => 0),
        'product_id' => array('int', 11, 'null' => 0),
        'sku_id' => array('int', 11, 'null' => 1),
        'affiliate' => array('int', 11, 'null' => 0, 'default' => 0),
        'create_datetime' => array('datetime', 'null' => 0, 'default' => 'CURRENT_TIMESTAMP'),
        'state' => array('varchar', 32, 'null' => 0),
        ':keys' => array(
            'PRIMARY' => 'id',
            'shop_apiextension_review_affiliate_review_id' => array('review_id'),
            'shop_apiextension_review_affiliate_contact_id_product_id_state' => array('contact_id', 'product_id', 'state'),
        ),
    ),
);
