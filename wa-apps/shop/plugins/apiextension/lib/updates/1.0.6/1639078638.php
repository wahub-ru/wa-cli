<?php

/**
 * UPDATE 1.0.6
 *
 * @author Steemy, created by 09.12.2021
 */

// Add field sku_id table shop_apiextension_reviews_affiliate
$model = new waModel();
try {
    $model->exec("ALTER TABLE `shop_apiextension_reviews_affiliate` ADD `sku_id` INT(11) NULL AFTER `product_id`");
} catch (waDbException $e) {

}