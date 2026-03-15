<?php
class shopPagevisitorcounterPluginBackendGraphAction extends waViewAction
{
    public function execute()
    {
        $model = new shopPagevisitorcounterModel();
        $pageId = waRequest::request('page_id', null, 'int');
        $period = waRequest::request('period', 'week');
        $groupBy = waRequest::request('group_by', 'date'); // 'date' или 'page'

        $key = 'graph_data_' . $pageId . '_' . $period . '_' . $groupBy;
        $cache = wa()->getCache();
        $data = $cache->get($key);

        if ($data === null) {
            if ($groupBy === 'date') {
                $sql = "SELECT date, COUNT(DISTINCT visitor_hash) as unique_views FROM {$model->getTableName()}";
                $params = [];
                if ($pageId) {
                    $sql .= " WHERE page_id = i:page_id";
                    $params['page_id'] = $pageId;
                }
                $sql .= " GROUP BY date ORDER BY date DESC LIMIT 30";
                $data = $model->query($sql, $params)->fetchAll('date', true);
            } else {
                $sql = "SELECT page_id, COUNT(DISTINCT visitor_hash) as unique_views FROM {$model->getTableName()} GROUP BY page_id ORDER BY unique_views DESC LIMIT 20";
                $data = $model->query($sql)->fetchAll('page_id', true);
            }
            $cache->set($key, $data, 3600); // Кешируем на 1 час
        }

        $this->view->assign('graph_data', $data);
        $this->view->assign('group_by', $groupBy);
    }
}