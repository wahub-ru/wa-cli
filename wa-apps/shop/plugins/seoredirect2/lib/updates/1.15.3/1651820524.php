<?php


try {
    $model = new waModel();

    $model->exec("ALTER TABLE `shop_seoredirect2_redirect` ADD `visit_datetime` DATETIME NULL DEFAULT NULL AFTER `edit_datetime`;");

} catch (Exception $e) {
}