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

if ($hasTable($model, 'shop_tgconsult_message') && !$hasColumn($model, 'shop_tgconsult_message', 'meta')) {
    try {
        $model->exec("ALTER TABLE `shop_tgconsult_message` ADD COLUMN `meta` TEXT NULL AFTER `text`");
    } catch (Exception $e) {
    }
}

if (
    $hasTable($model, 'shop_tgconsult_message')
    && $hasColumn($model, 'shop_tgconsult_message', 'payload')
    && $hasColumn($model, 'shop_tgconsult_message', 'meta')
) {
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

if ($hasTable($model, 'shop_tgconsult_chat') && !$hasColumn($model, 'shop_tgconsult_chat', 'last_read_visitor_id')) {
    try {
        $model->exec("ALTER TABLE `shop_tgconsult_chat` ADD COLUMN `last_read_visitor_id` INT UNSIGNED NOT NULL DEFAULT 0 AFTER `updated`");
    } catch (Exception $e) {
    }
}

$normalize_position = static function ($value) {
    $raw = strtolower(trim((string) $value));
    if ($raw === '') {
        return 'right';
    }
    if (in_array($raw, ['left', 'l', '1', '0', 'true', 'yes', 'on'], true) || strpos($raw, 'left') !== false) {
        return 'left';
    }
    return 'right';
};

$get = static function (array $array, $key, $default = null) {
    return array_key_exists($key, $array) ? $array[$key] : $default;
};

try {
    /** @var shopPlugin $plugin */
    $plugin = wa('shop')->getPlugin('tgconsult');
    $settings = (array) $plugin->getSettings();
    $changed = false;

    $position = $get($settings, 'widget_position', $get($settings, 'position', 'right'));
    $normalized_position = $normalize_position($position);
    if (!isset($settings['widget_position']) || $settings['widget_position'] !== $normalized_position) {
        $settings['widget_position'] = $normalized_position;
        $changed = true;
    }
    if (!isset($settings['position']) || $settings['position'] !== $normalized_position) {
        $settings['position'] = $normalized_position;
        $changed = true;
    }

    $offset_side = (int) $get($settings, 'widget_offset_side', $get($settings, 'offset_side', 22));
    if ($offset_side < 0) {
        $offset_side = 0;
    } elseif ($offset_side > 500) {
        $offset_side = 500;
    }
    if (!isset($settings['widget_offset_side']) || (int) $settings['widget_offset_side'] !== $offset_side) {
        $settings['widget_offset_side'] = $offset_side;
        $changed = true;
    }
    if (!isset($settings['offset_side']) || (int) $settings['offset_side'] !== $offset_side) {
        $settings['offset_side'] = $offset_side;
        $changed = true;
    }

    $offset_bottom = (int) $get($settings, 'widget_offset_bottom', $get($settings, 'offset_bottom', 70));
    if ($offset_bottom < 0) {
        $offset_bottom = 0;
    } elseif ($offset_bottom > 500) {
        $offset_bottom = 500;
    }
    if (!isset($settings['widget_offset_bottom']) || (int) $settings['widget_offset_bottom'] !== $offset_bottom) {
        $settings['widget_offset_bottom'] = $offset_bottom;
        $changed = true;
    }
    if (!isset($settings['offset_bottom']) || (int) $settings['offset_bottom'] !== $offset_bottom) {
        $settings['offset_bottom'] = $offset_bottom;
        $changed = true;
    }

    if (!isset($settings['manager_name_mode'])) {
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
