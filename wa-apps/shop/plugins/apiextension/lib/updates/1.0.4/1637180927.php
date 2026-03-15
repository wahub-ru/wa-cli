<?php

/**
 * UPDATE 1.0.4
 *
 * @author Steemy, created by 17.11.2021
 */

// Update field affiliate default 0 table shop_apiextension_reviews_affiliate
$model = new waModel();
try {
    $model->exec("ALTER TABLE `shop_apiextension_reviews_affiliate` CHANGE COLUMN affiliate affiliate INT(11) NOT NULL DEFAULT 0");
} catch (waDbException $e) {

}