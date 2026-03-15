<?php
/**
 * Корректное создание и восстановление схемы.
 * Идемпотентно: допускает повторные запуски и частично сломанные установки.
 */
$model = new waModel();

if (!function_exists('tgc_table_exists')) {
    function tgc_table_exists(waModel $m, $table)
    {
        try {
            return (bool) $m->query("SHOW TABLES LIKE ?", $table)->fetch();
        } catch (Exception $e) {
            return false;
        }
    }
}

if (!function_exists('tgc_col_exists')) {
    function tgc_col_exists(waModel $m, $table, $col)
    {
        try {
            return (bool) $m->query("SHOW COLUMNS FROM `{$table}` LIKE ?", $col)->fetch();
        } catch (Exception $e) {
            return false;
        }
    }
}

if (!function_exists('tgc_add_index_if_absent')) {
    function tgc_add_index_if_absent(waModel $m, $table, $key_name, $sql_add)
    {
        try {
            $row = $m->query("SHOW INDEX FROM `{$table}` WHERE Key_name=?", $key_name)->fetch();
            if (!$row) {
                $m->exec($sql_add);
            }
        } catch (Exception $e) {
        }
    }
}

if (!function_exists('tgc_primary_is_id')) {
    function tgc_primary_is_id(waModel $m, $table)
    {
        try {
            $rows = $m->query("SHOW KEYS FROM `{$table}` WHERE Key_name='PRIMARY'")->fetchAll();
            if (!$rows) {
                return false;
            }
            return count($rows) === 1 && strtolower((string) $rows[0]['Column_name']) === 'id';
        } catch (Exception $e) {
            return false;
        }
    }
}

if (!function_exists('tgc_add_column_if_absent')) {
    function tgc_add_column_if_absent(waModel $m, $table, $column, $sql_add)
    {
        if (!tgc_col_exists($m, $table, $column)) {
            try {
                $m->exec($sql_add);
            } catch (Exception $e) {
            }
        }
    }
}

/** CHAT */
if (!tgc_table_exists($model, 'shop_tgconsult_chat')) {
    $model->exec("
        CREATE TABLE `shop_tgconsult_chat` (
          `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
          `customer_id` INT UNSIGNED NOT NULL DEFAULT 0,
          `session_id` VARCHAR(64) NULL DEFAULT NULL,
          `token` CHAR(32) NOT NULL,
          `title` VARCHAR(255) NOT NULL DEFAULT '',
          `created` DATETIME NOT NULL,
          `updated` DATETIME NOT NULL,
          `last_read_visitor_id` INT UNSIGNED NOT NULL DEFAULT 0,
          `closed` TINYINT(1) NOT NULL DEFAULT 0,
          PRIMARY KEY (`id`),
          UNIQUE KEY `token` (`token`),
          KEY `customer_session` (`customer_id`, `session_id`),
          KEY `updated` (`updated`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
    ");
}

if (tgc_table_exists($model, 'shop_tgconsult_chat')) {
    if (!tgc_col_exists($model, 'shop_tgconsult_chat', 'id')) {
        try {
            $model->exec("ALTER TABLE `shop_tgconsult_chat` DROP PRIMARY KEY");
        } catch (Exception $e) {
        }
        $model->exec("
          ALTER TABLE `shop_tgconsult_chat`
          ADD COLUMN `id` INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY FIRST
        ");
    } else {
        try {
            $model->exec("
              ALTER TABLE `shop_tgconsult_chat`
              MODIFY `id` INT UNSIGNED NOT NULL AUTO_INCREMENT
            ");
        } catch (Exception $e) {
        }
        if (!tgc_primary_is_id($model, 'shop_tgconsult_chat')) {
            try {
                $model->exec("ALTER TABLE `shop_tgconsult_chat` DROP PRIMARY KEY");
            } catch (Exception $e) {
            }
            try {
                $model->exec("ALTER TABLE `shop_tgconsult_chat` ADD PRIMARY KEY (`id`)");
            } catch (Exception $e) {
            }
        }
    }

    tgc_add_column_if_absent($model, 'shop_tgconsult_chat', 'customer_id', "ALTER TABLE `shop_tgconsult_chat` ADD COLUMN `customer_id` INT UNSIGNED NOT NULL DEFAULT 0 AFTER `id`");
    tgc_add_column_if_absent($model, 'shop_tgconsult_chat', 'session_id', "ALTER TABLE `shop_tgconsult_chat` ADD COLUMN `session_id` VARCHAR(64) NULL DEFAULT NULL AFTER `customer_id`");

    if (!tgc_col_exists($model, 'shop_tgconsult_chat', 'token')) {
        try {
            $model->exec("ALTER TABLE `shop_tgconsult_chat` ADD COLUMN `token` CHAR(32) NULL DEFAULT NULL AFTER `session_id`");
        } catch (Exception $e) {
        }
    }
    if (tgc_col_exists($model, 'shop_tgconsult_chat', 'token')) {
        try {
            $model->exec("UPDATE `shop_tgconsult_chat` SET `token` = LPAD(LOWER(HEX(`id`)), 32, '0') WHERE `token` IS NULL OR `token` = ''");
        } catch (Exception $e) {
        }
        try {
            $model->exec("ALTER TABLE `shop_tgconsult_chat` MODIFY `token` CHAR(32) NOT NULL");
        } catch (Exception $e) {
        }
    }

    tgc_add_column_if_absent($model, 'shop_tgconsult_chat', 'title', "ALTER TABLE `shop_tgconsult_chat` ADD COLUMN `title` VARCHAR(255) NOT NULL DEFAULT '' AFTER `token`");
    if (tgc_col_exists($model, 'shop_tgconsult_chat', 'title')) {
        try {
            $model->exec("UPDATE `shop_tgconsult_chat` SET `title` = '' WHERE `title` IS NULL");
        } catch (Exception $e) {
        }
        try {
            $model->exec("ALTER TABLE `shop_tgconsult_chat` MODIFY `title` VARCHAR(255) NOT NULL DEFAULT ''");
        } catch (Exception $e) {
        }
    }

    tgc_add_column_if_absent($model, 'shop_tgconsult_chat', 'created', "ALTER TABLE `shop_tgconsult_chat` ADD COLUMN `created` DATETIME NULL DEFAULT NULL AFTER `title`");
    tgc_add_column_if_absent($model, 'shop_tgconsult_chat', 'updated', "ALTER TABLE `shop_tgconsult_chat` ADD COLUMN `updated` DATETIME NULL DEFAULT NULL AFTER `created`");
    tgc_add_column_if_absent($model, 'shop_tgconsult_chat', 'last_read_visitor_id', "ALTER TABLE `shop_tgconsult_chat` ADD COLUMN `last_read_visitor_id` INT UNSIGNED NOT NULL DEFAULT 0 AFTER `updated`");
    tgc_add_column_if_absent($model, 'shop_tgconsult_chat', 'closed', "ALTER TABLE `shop_tgconsult_chat` ADD COLUMN `closed` TINYINT(1) NOT NULL DEFAULT 0 AFTER `last_read_visitor_id`");

    tgc_add_index_if_absent($model, 'shop_tgconsult_chat', 'token', "ALTER TABLE `shop_tgconsult_chat` ADD UNIQUE KEY `token` (`token`)");
    tgc_add_index_if_absent($model, 'shop_tgconsult_chat', 'customer_session', "ALTER TABLE `shop_tgconsult_chat` ADD KEY `customer_session` (`customer_id`, `session_id`)");
    tgc_add_index_if_absent($model, 'shop_tgconsult_chat', 'updated', "ALTER TABLE `shop_tgconsult_chat` ADD KEY `updated` (`updated`)");
}

/** MESSAGE */
if (!tgc_table_exists($model, 'shop_tgconsult_message')) {
    $model->exec("
        CREATE TABLE `shop_tgconsult_message` (
          `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
          `chat_id` INT UNSIGNED NOT NULL,
          `sender` ENUM('visitor','manager') NOT NULL,
          `text` MEDIUMTEXT NOT NULL,
          `meta` TEXT NULL,
          `created` DATETIME NOT NULL,
          PRIMARY KEY (`id`),
          KEY `chat` (`chat_id`),
          KEY `created` (`created`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
    ");
}

if (tgc_table_exists($model, 'shop_tgconsult_message')) {
    if (!tgc_col_exists($model, 'shop_tgconsult_message', 'id')) {
        try {
            $model->exec("ALTER TABLE `shop_tgconsult_message` DROP PRIMARY KEY");
        } catch (Exception $e) {
        }
        $model->exec("
          ALTER TABLE `shop_tgconsult_message`
          ADD COLUMN `id` INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY FIRST
        ");
    } else {
        try {
            $model->exec("
              ALTER TABLE `shop_tgconsult_message`
              MODIFY `id` INT UNSIGNED NOT NULL AUTO_INCREMENT
            ");
        } catch (Exception $e) {
        }
        if (!tgc_primary_is_id($model, 'shop_tgconsult_message')) {
            try {
                $model->exec("ALTER TABLE `shop_tgconsult_message` DROP PRIMARY KEY");
            } catch (Exception $e) {
            }
            try {
                $model->exec("ALTER TABLE `shop_tgconsult_message` ADD PRIMARY KEY (`id`)");
            } catch (Exception $e) {
            }
        }
    }

    tgc_add_column_if_absent($model, 'shop_tgconsult_message', 'chat_id', "ALTER TABLE `shop_tgconsult_message` ADD COLUMN `chat_id` INT UNSIGNED NOT NULL DEFAULT 0 AFTER `id`");
    tgc_add_column_if_absent($model, 'shop_tgconsult_message', 'sender', "ALTER TABLE `shop_tgconsult_message` ADD COLUMN `sender` ENUM('visitor','manager') NOT NULL DEFAULT 'visitor' AFTER `chat_id`");
    tgc_add_column_if_absent($model, 'shop_tgconsult_message', 'text', "ALTER TABLE `shop_tgconsult_message` ADD COLUMN `text` MEDIUMTEXT NULL AFTER `sender`");
    tgc_add_column_if_absent($model, 'shop_tgconsult_message', 'meta', "ALTER TABLE `shop_tgconsult_message` ADD COLUMN `meta` TEXT NULL AFTER `text`");
    tgc_add_column_if_absent($model, 'shop_tgconsult_message', 'created', "ALTER TABLE `shop_tgconsult_message` ADD COLUMN `created` DATETIME NULL DEFAULT NULL AFTER `meta`");

    tgc_add_index_if_absent($model, 'shop_tgconsult_message', 'chat', "ALTER TABLE `shop_tgconsult_message` ADD KEY `chat` (`chat_id`)");
    tgc_add_index_if_absent($model, 'shop_tgconsult_message', 'created', "ALTER TABLE `shop_tgconsult_message` ADD KEY `created` (`created`)");
}
