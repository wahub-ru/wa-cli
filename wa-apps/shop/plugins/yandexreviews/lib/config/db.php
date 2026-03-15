<?php

$model = new waModel();

/**
 * Таблица компаний
 */
try {
    $model->exec("CREATE TABLE IF NOT EXISTS `shop_yandexreviews_company` (
      `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
      `yandex_company_id` VARCHAR(32) NOT NULL,
      `name` VARCHAR(255) NULL,
      `rating` DECIMAL(3,2) NULL,
      `reviews_total` INT UNSIGNED NULL,
      `url` TEXT NULL,
      `last_fetch_datetime` DATETIME NULL,
      `create_datetime` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
      PRIMARY KEY (`id`),
      UNIQUE KEY `uniq_company` (`yandex_company_id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
} catch (Exception $e) {}

/**
 * Таблица отзывов (актуальная схема)
 */
try {
    $model->exec("CREATE TABLE IF NOT EXISTS `shop_yandexreviews_review` (
      `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
      `company_id` INT UNSIGNED NOT NULL,
      `yandex_review_id` VARCHAR(64) NOT NULL,
      `author_name` VARCHAR(255) NULL,
      `author_avatar` TEXT NULL,
      `author_avatar_local` VARCHAR(255) NULL,
      `rating` TINYINT UNSIGNED NULL,
      `text` MEDIUMTEXT NULL,
      `photos_json` MEDIUMTEXT NULL,
      `permalink` TEXT NULL,
      `review_datetime` DATETIME NULL,
      `create_datetime` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
      PRIMARY KEY (`id`),
      UNIQUE KEY `uniq_review_company` (`company_id`, `yandex_review_id`),
      KEY `idx_company` (`company_id`),
      KEY `idx_review_date` (`review_datetime`),
      KEY `idx_review_rating` (`rating`),
      CONSTRAINT `fk_reviews_company`
        FOREIGN KEY (`company_id`) REFERENCES `shop_yandexreviews_company` (`id`)
        ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
} catch (Exception $e) {}

/**
 * Миграции «поверх» уже существующей таблицы
 */
try {
    // добавить локальную аватарку, если её ещё нет
    $model->exec("ALTER TABLE `shop_yandexreviews_review`
      ADD COLUMN `author_avatar_local` VARCHAR(255) NULL AFTER `author_avatar`");
} catch (Exception $e) {}

try {
    // заменить уникальность на (company_id, yandex_review_id)
    $model->exec("ALTER TABLE `shop_yandexreviews_review`
      DROP INDEX `uniq_review`,
      ADD UNIQUE KEY `uniq_review_company` (`company_id`, `yandex_review_id`)");
} catch (Exception $e) {}

try {
    // индексы для сортировок/фильтров
    $model->exec("ALTER TABLE `shop_yandexreviews_review`
      ADD KEY `idx_review_date` (`review_datetime`)");
} catch (Exception $e) {}

try {
    $model->exec("ALTER TABLE `shop_yandexreviews_review`
      ADD KEY `idx_review_rating` (`rating`)");
} catch (Exception $e) {}
