<?php
class shopPagevisitorcounterPluginBackendDeleteAction extends waViewAction
{
    public function execute()
    {
        $model = new shopPagevisitorcounterModel();
        $period = waRequest::request('period', 'all');
        $pageId = waRequest::request('page_id', null, 'int');

        $conditions = [];
        $params = [];

        if ($pageId) {
            $conditions[] = 'page_id = i:page_id';
            $params['page_id'] = $pageId;
        }

        switch ($period) {
            case 'today':
                $conditions[] = 'date = s:today';
                $params['today'] = date('Y-m-d');
                break;
            case 'yesterday':
                $conditions[] = 'date = s:yesterday';
                $params['yesterday'] = date('Y-m-d', strtotime('-1 day'));
                break;
            case 'week':
                $conditions[] = 'date >= s:week_start';
                $params['week_start'] = date('Y-m-d', strtotime('-1 week'));
                break;
            case 'month':
                $conditions[] = 'date >= s:month_start';
                $params['month_start'] = date('Y-m-d', strtotime('-1 month'));
                break;
            case 'year':
                $conditions[] = 'date >= s:year_start';
                $params['year_start'] = date('Y-m-d', strtotime('-1 year'));
                break;
            case 'custom':
                $startDate = waRequest::request('start_date');
                $endDate = waRequest::request('end_date');
                if ($startDate && $endDate) {
                    $conditions[] = 'date BETWEEN s:start_date AND s:end_date';
                    $params['start_date'] = $startDate;
                    $params['end_date'] = $endDate;
                }
                break;
        }

        $where = $conditions ? 'WHERE ' . implode(' AND ', $conditions) : '';
        $sql = "DELETE FROM {$model->getTableName()} $where";

        $deleted = $model->exec($sql, $params);
        $this->clearCache($pageId);

        $this->view->assign('deleted', $deleted);
    }

    private function clearCache($pageId = null)
    {
        $cache = wa()->getCache();
        $patterns = ['page_views_*', 'graph_data_*', 'stats_summary_*'];
        foreach ($patterns as $pattern) {
            $cache->delete($pattern);
        }
    }
}