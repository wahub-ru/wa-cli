<?php


try {
    $model = new waModel();

    $model->exec("DELETE FROM `shop_seoredirect2_shop_urls` WHERE url='' AND id=0;");

} catch (Exception $e) {
}