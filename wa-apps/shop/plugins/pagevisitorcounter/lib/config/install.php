<?php

// Создаем экземпляр модели для проверки/создания таблицы
$model = new waModel();

// Проверяем существование таблицы (на всякий случай)
try {
    $model->query("SELECT COUNT(*) FROM shop_pagevisitorcounter WHERE 0");
} catch (waDbException $e) {
    // Таблица не существует, создаем ее через db.php схему
    $db_path = wa()->getAppPath('plugins/pagevisitorcounter/lib/config/db.php', 'shop');
    $schema = include($db_path);

    $migrations = new waDbMigration($model, $schema);
    $migrations->createTables();
}

// Создаем кеширующий адаптер, если он еще не создан
try {
    $cache = wa()->getCache();
    if (!$cache) {
        // Пытаемся создать кеш на основе memcached, если доступен
        if (class_exists('Memcached')) {
            $cache_config = array(
                'type' => 'memcached',
                'servers' => array(
                    array('host' => 'localhost', 'port' => 11211, 'weight' => 1)
                )
            );
            wa()->setCache($cache_config);
        }
    }
} catch (Exception $e) {
    // В случае ошибки просто логируем, но не прерываем установку
    waLog::log('Ошибка при инициализации кеша для pagevisitorcounter: ' . $e->getMessage());
}

// Дополнительные действия при установке
// Например, создание начальных настроек плагина
$plugin_id = 'pagevisitorcounter';
$app_id = 'shop';

// Сохраняем настройки по умолчанию
$default_settings = array(
    'bot_patterns' => 'Googlebot|Bingbot|YandexBot|DuckDuckBot|baiduspider|sogou|exabot|facebot|ia_archiver',
    'cache_ttl' => 600,
    'track_products' => 1,
    'track_categories' => 1,
    'track_pages' => 1,
);

try {
    $app_settings_model = new waAppSettingsModel();
    foreach ($default_settings as $key => $value) {
        $app_settings_model->set($app_id . '.' . $plugin_id, $key, $value);
    }
} catch (Exception $e) {
    waLog::log('Ошибка при сохранении настроек плагина pagevisitorcounter: ' . $e->getMessage());
}