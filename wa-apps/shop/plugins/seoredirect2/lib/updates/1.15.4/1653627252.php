<?php


try {
    $model = new waModel();

    $model->exec("ALTER TABLE `shop_seoredirect2_redirect` CHANGE `visit_datetime` `visit_datetime` DATETIME NULL DEFAULT NULL;");

} catch (Exception $e) {
}