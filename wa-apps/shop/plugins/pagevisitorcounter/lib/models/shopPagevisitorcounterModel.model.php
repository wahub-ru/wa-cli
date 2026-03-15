<?php

class shopPagevisitorcounterModel extends waModel
{
    protected $table = 'shop_pagevisitorcounter';

    public function __construct()
    {
        parent::__construct();

        // Получаем настройки TTL из конфигурации плагина
        $this->cache_ttl = wa()->getSetting('cache_ttl', 600, array('shop', 'pagevisitorcounter'));
    }

    public function getViewsCount($pageId, $period = null)
    {
        $key = 'page_views_' . $pageId . '_' . md5(serialize($period));
        $cache = wa()->getCache();

        if ($cache) {
            $views = $cache->get($key);
            if ($views !== null) {
                return $views;
            }
        }

        $sql = "SELECT COUNT(DISTINCT visitor_hash) as unique_views FROM {$this->table} WHERE page_id = i:page_id";
        $params = ['page_id' => $pageId];

        if ($period === 'today') {
            $sql .= " AND date = s:date";
            $params['date'] = date('Y-m-d');
        } elseif (is_array($period)) {
            $sql .= " AND date BETWEEN s:start AND s:end";
            $params['start'] = $period['start'];
            $params['end'] = $period['end'];
        }

        $result = $this->query($sql, $params)->fetchAssoc();
        $views = $result ? $result['unique_views'] : 0;

        if ($cache) {
            $cache->set($key, $views, $this->cache_ttl);
        }

        return $views;
    }

    public function trackView($pageId, $visitorHash)
    {
        // Проверяем, нужно ли отслеживать этот тип страницы
        $page_type = $this->getPageType($pageId);
        $track_setting = wa()->getSetting('track_' . $page_type . 's', 1, array('shop', 'pagevisitorcounter'));

        if (!$track_setting) {
            return false;
        }

        $dateToday = date('Y-m-d');
        $sql = "INSERT INTO {$this->table} (page_id, visitor_hash, date, views) 
                VALUES (i:page_id, s:hash, s:date, 1) 
                ON DUPLICATE KEY UPDATE views = views + 1";

        $result = $this->query($sql, array(
            'page_id' => $pageId,
            'hash' => $visitorHash,
            'date' => $dateToday
        ));

        if ($result) {
            $this->clearCache($pageId);
        }

        return $result;
    }

    private function getPageType($pageId)
    {
        // Определяем тип страницы по ее ID
        // Это упрощенная реализация, может потребоваться доработка
        $product_model = new shopProductModel();
        if ($product_model->getById($pageId)) {
            return 'product';
        }

        $category_model = new shopCategoryModel();
        if ($category_model->getById($pageId)) {
            return 'category';
        }

        $page_model = new shopPageModel();
        if ($page_model->getById($pageId)) {
            return 'page';
        }

        return 'unknown';
    }

    private function clearCache($pageId = null)
    {
        $cache = wa()->getCache();
        if (!$cache) {
            return;
        }

        $patterns = ['page_views_*', 'graph_data_*', 'stats_summary_*'];
        foreach ($patterns as $pattern) {
            $cache->delete($pattern);
        }
    }
}