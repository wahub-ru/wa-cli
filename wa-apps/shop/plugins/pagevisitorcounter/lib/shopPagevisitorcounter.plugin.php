<?php

class shopPagevisitorcounterPlugin extends shopPlugin
{
    public function frontendHead()
    {
        // Проверяем, включено ли отслеживание для текущего типа страницы
        $currentPageType = null;
        $pageId = null;

        if (waRequest::param('product_url')) {
            $currentPageType = 'product';
            $product = waRequest::param('product');
            $pageId = $product ? $product['id'] : null;

            // Проверяем настройку track_products
            if (!wa()->getSetting('track_products', 1, array('shop', 'pagevisitorcounter'))) {
                return;
            }
        } elseif (waRequest::param('category_url')) {
            $currentPageType = 'category';
            $category = waRequest::param('category');
            $pageId = $category ? $category['id'] : null;

            // Проверяем настройку track_categories
            if (!wa()->getSetting('track_categories', 1, array('shop', 'pagevisitorcounter'))) {
                return;
            }
        }
        // Аналогично для других типов страниц

        if ($pageId) {
            $trackUrl = wa()->getRouteUrl('shop/frontend/trackPageVisit', ['plugin' => 'pagevisitorcounter'], true);
            wa()->getResponse()->addJsFile($this->getPluginStaticUrl() . 'js/track.js');
            wa()->getResponse()->addInlineJs("
                window.pageVisitorCounterData = {
                    pageType: '" . $currentPageType . "',
                    pageId: " . (int)$pageId . ",
                    trackUrl: '" . $trackUrl . "'
                };
            ");
        }
    }

    public static function getViews($pageId, $period = null)
    {
        if (!$pageId) {
            return 0;
        }
        $model = new shopPagevisitorcounterModel();
        return $model->getViewsCount($pageId, $period);
    }

    // Метод для получения настроек плагина
    public static function getSettings()
    {
        $app_settings_model = new waAppSettingsModel();
        $settings = $app_settings_model->get('shop.pagevisitorcounter');
        return $settings;
    }

    // Метод для сохранения настроек плагина
    public static function saveSettings($settings)
    {
        $app_settings_model = new waAppSettingsModel();
        foreach ($settings as $key => $value) {
            $app_settings_model->set('shop.pagevisitorcounter', $key, $value);
        }

        // Очищаем кеш после изменения настроек
        $cache = wa()->getCache();
        if ($cache) {
            $patterns = ['page_views_*', 'graph_data_*', 'stats_summary_*'];
            foreach ($patterns as $pattern) {
                $cache->delete($pattern);
            }
        }
    }
}