<?php

/**
 * UPDATE 1.0.1
 *
 * @author Steemy, created by 17.08.2021
 */


$model = new waModel();

try {
    $model->query('SELECT apiextension_experience FROM shop_product_reviews WHERE 0');
} catch(waDbException $e) {
    $model->query('ALTER TABLE `shop_product_reviews` ADD `apiextension_experience` TEXT NULL AFTER `text`');
}

try {
    $model->query('SELECT apiextension_dignity FROM shop_product_reviews WHERE 0');
} catch(waDbException $e) {
    $model->query('ALTER TABLE `shop_product_reviews` ADD `apiextension_dignity` TEXT NULL AFTER `text`');
}

try {
    $model->query('SELECT apiextension_limitations FROM shop_product_reviews WHERE 0');
} catch(waDbException $e) {
    $model->query('ALTER TABLE `shop_product_reviews` ADD `apiextension_limitations` TEXT NULL AFTER `text`');
}

try {
    $model->query('SELECT apiextension_recommend FROM shop_product_reviews WHERE 0');
} catch(waDbException $e) {
    $model->query('ALTER TABLE `shop_product_reviews` ADD `apiextension_recommend` INT(1) NULL AFTER `text`');
}