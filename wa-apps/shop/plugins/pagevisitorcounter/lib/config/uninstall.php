<?php

// Удаляем настройки плагина
$app_settings_model = new waAppSettingsModel();
$plugin_id = 'pagevisitorcounter';
$app_id = 'shop';

// Получаем все настройки плагина
$settings = $app_settings_model->get($app_id . '.' . $plugin_id);

// Удаляем каждую настройку
foreach ($settings as $key => $value) {
    $app_settings_model->del($app_id . '.' . $plugin_id, $key);
}

// Очищаем кеш, связанный с плагином
$cache = wa()->getCache();
if ($cache) {
    $patterns = ['page_views_*', 'graph_data_*', 'stats_summary_*'];
    foreach ($patterns as $pattern) {
        $cache->delete($pattern);
    }
}

// Примечание: Таблица shop_pagevisitorcounter будет автоматически удалена
// системой Webasyst, так как она описана в db.php