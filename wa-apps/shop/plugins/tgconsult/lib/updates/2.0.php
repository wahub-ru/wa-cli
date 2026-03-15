<?php

$model = new waModel();

$hasTable = static function (waModel $m, $table) {
    try {
        return (bool) $m->query("SHOW TABLES LIKE ?", $table)->fetchField();
    } catch (Exception $e) {
        return false;
    }
};

$hasColumn = static function (waModel $m, $table, $column) {
    try {
        return (bool) $m->query("SHOW COLUMNS FROM `{$table}` LIKE ?", $column)->fetch();
    } catch (Exception $e) {
        return false;
    }
};

$hasIndex = static function (waModel $m, $table, $key_name) {
    try {
        return (bool) $m->query("SHOW INDEX FROM `{$table}` WHERE Key_name=?", $key_name)->fetch();
    } catch (Exception $e) {
        return false;
    }
};

$addIndexIfMissing = static function (waModel $m, $table, $key_name, $sql) use ($hasIndex) {
    if (!$hasIndex($m, $table, $key_name)) {
        try {
            $m->exec($sql);
        } catch (Exception $e) {
        }
    }
};

$get = static function (array $array, $key, $default = null) {
    return array_key_exists($key, $array) ? $array[$key] : $default;
};

$normalizePosition = static function ($value) {
    $raw = strtolower(trim((string) $value));
    if ($raw === '') {
        return 'right';
    }
    if (in_array($raw, ['left', 'l', '1', '0', 'true', 'yes', 'on'], true) || strpos($raw, 'left') !== false) {
        return 'left';
    }
    return 'right';
};

if ($hasTable($model, 'shop_tgconsult_chat')) {
    if ($hasColumn($model, 'shop_tgconsult_chat', 'title')) {
        try {
            $model->exec("UPDATE `shop_tgconsult_chat` SET `title` = '' WHERE `title` IS NULL");
        } catch (Exception $e) {
        }
        try {
            $model->exec("ALTER TABLE `shop_tgconsult_chat` MODIFY `title` VARCHAR(255) NOT NULL DEFAULT ''");
        } catch (Exception $e) {
        }
    }

    if (!$hasColumn($model, 'shop_tgconsult_chat', 'last_read_visitor_id')) {
        try {
            $model->exec("ALTER TABLE `shop_tgconsult_chat` ADD COLUMN `last_read_visitor_id` INT UNSIGNED NOT NULL DEFAULT 0 AFTER `updated`");
        } catch (Exception $e) {
        }
    }

    $addIndexIfMissing(
        $model,
        'shop_tgconsult_chat',
        'token',
        "ALTER TABLE `shop_tgconsult_chat` ADD UNIQUE KEY `token` (`token`)"
    );
    $addIndexIfMissing(
        $model,
        'shop_tgconsult_chat',
        'customer_session',
        "ALTER TABLE `shop_tgconsult_chat` ADD KEY `customer_session` (`customer_id`, `session_id`)"
    );
    $addIndexIfMissing(
        $model,
        'shop_tgconsult_chat',
        'updated',
        "ALTER TABLE `shop_tgconsult_chat` ADD KEY `updated` (`updated`)"
    );
}

if ($hasTable($model, 'shop_tgconsult_message')) {
    if (!$hasColumn($model, 'shop_tgconsult_message', 'meta')) {
        try {
            $model->exec("ALTER TABLE `shop_tgconsult_message` ADD COLUMN `meta` TEXT NULL AFTER `text`");
        } catch (Exception $e) {
        }
    }

    if ($hasColumn($model, 'shop_tgconsult_message', 'payload') && $hasColumn($model, 'shop_tgconsult_message', 'meta')) {
        try {
            $model->exec("
                UPDATE `shop_tgconsult_message`
                   SET `meta` = `payload`
                 WHERE (`meta` IS NULL OR `meta` = '')
                   AND `payload` IS NOT NULL
                   AND `payload` <> ''
            ");
        } catch (Exception $e) {
        }
    }

    $addIndexIfMissing(
        $model,
        'shop_tgconsult_message',
        'chat',
        "ALTER TABLE `shop_tgconsult_message` ADD KEY `chat` (`chat_id`)"
    );
    $addIndexIfMissing(
        $model,
        'shop_tgconsult_message',
        'created',
        "ALTER TABLE `shop_tgconsult_message` ADD KEY `created` (`created`)"
    );
}

try {
    /** @var shopPlugin $plugin */
    $plugin = wa('shop')->getPlugin('tgconsult');
    $settings = (array) $plugin->getSettings();
    $changed = false;

    $position = $get($settings, 'widget_position', $get($settings, 'position', 'right'));
    $normalizedPosition = $normalizePosition($position);
    if (!isset($settings['widget_position']) || (string) $settings['widget_position'] !== $normalizedPosition) {
        $settings['widget_position'] = $normalizedPosition;
        $changed = true;
    }
    if (!isset($settings['position']) || (string) $settings['position'] !== $normalizedPosition) {
        $settings['position'] = $normalizedPosition;
        $changed = true;
    }

    $offsetSide = (int) $get($settings, 'widget_offset_side', $get($settings, 'offset_side', 22));
    $offsetSide = max(0, min(500, $offsetSide));
    if (!isset($settings['widget_offset_side']) || (int) $settings['widget_offset_side'] !== $offsetSide) {
        $settings['widget_offset_side'] = $offsetSide;
        $changed = true;
    }
    if (!isset($settings['offset_side']) || (int) $settings['offset_side'] !== $offsetSide) {
        $settings['offset_side'] = $offsetSide;
        $changed = true;
    }

    $offsetBottom = (int) $get($settings, 'widget_offset_bottom', $get($settings, 'offset_bottom', 70));
    $offsetBottom = max(0, min(500, $offsetBottom));
    if (!isset($settings['widget_offset_bottom']) || (int) $settings['widget_offset_bottom'] !== $offsetBottom) {
        $settings['widget_offset_bottom'] = $offsetBottom;
        $changed = true;
    }
    if (!isset($settings['offset_bottom']) || (int) $settings['offset_bottom'] !== $offsetBottom) {
        $settings['offset_bottom'] = $offsetBottom;
        $changed = true;
    }

    if (!isset($settings['manager_name_mode']) || !in_array((string) $settings['manager_name_mode'], ['settings', 'responder'], true)) {
        $settings['manager_name_mode'] = 'settings';
        $changed = true;
    }

    if (!isset($settings['working_hours_enabled'])) {
        $settings['working_hours_enabled'] = 0;
        $changed = true;
    }

    if (!isset($settings['working_timezone']) || !in_array((string) $settings['working_timezone'], timezone_identifiers_list(), true)) {
        $settings['working_timezone'] = date_default_timezone_get() ?: 'UTC';
        $changed = true;
    }

    if (!isset($settings['offhours_autoreply']) || trim((string) $settings['offhours_autoreply']) === '') {
        $settings['offhours_autoreply'] = 'Сейчас мы вне графика работы. Оставьте, пожалуйста, ваши контакты для связи, и мы ответим в рабочее время.';
        $changed = true;
    }

    if ($changed) {
        $plugin->saveSettings($settings);
    }
} catch (Exception $e) {
}
