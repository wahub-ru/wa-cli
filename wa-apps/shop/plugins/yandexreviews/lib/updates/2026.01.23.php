<?php

$model = new waModel();

try {
    $column = $model->query("SHOW COLUMNS FROM `shop_yandexreviews_review` LIKE 'photos_json'")->fetch();
    if (!$column) {
        $model->exec("ALTER TABLE `shop_yandexreviews_review` ADD `photos_json` MEDIUMTEXT NULL");
    }
} catch (Exception $e) {
    // ignore: таблица может отсутствовать в окружении
}
