<?php

$model = new waModel();

// Проверка наличия таблицы 'blog_post'
$table_exists = $model->query("SHOW TABLES LIKE 'blog_post'")->fetch();
if ($table_exists) {
    // Проверка наличия столбца 'categories'
    $column_exists = $model->query("SHOW COLUMNS FROM `blog_post` LIKE 'categories'")->fetch();
    if (!$column_exists) {
        // Добавление столбца 'categories'
        $model->exec("ALTER TABLE `blog_post` ADD COLUMN `categories` VARCHAR(255) DEFAULT NULL");
    }
}