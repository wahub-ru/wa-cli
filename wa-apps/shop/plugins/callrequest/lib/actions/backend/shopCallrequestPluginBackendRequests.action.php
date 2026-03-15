<?php

class shopCallrequestPluginBackendRequestsAction extends waViewAction
{
    public function execute()
    {
        $tpl = wa()->getAppPath('plugins/callrequest/templates/actions/backend/Requests.html', 'shop');
        $this->setTemplate($tpl);
        if (class_exists('shopBackendLayout')) {
            $this->setLayout(new shopBackendLayout());
        }

        $m = new shopCallrequestPluginRequestModel();

        $page   = max(1, waRequest::get('page', 1, waRequest::TYPE_INT));
        $limit  = 30; // по 30 на странице
        $offset = ($page - 1) * $limit;

        $status        = waRequest::get('status',   '', waRequest::TYPE_STRING_TRIM);
        $q_name        = waRequest::get('q_name',   '', waRequest::TYPE_STRING_TRIM);
        $q_phone       = waRequest::get('q_phone',  '', waRequest::TYPE_STRING_TRIM);
        $date_from_raw = waRequest::get('date_from','', waRequest::TYPE_STRING_TRIM);
        $date_to_raw   = waRequest::get('date_to',  '', waRequest::TYPE_STRING_TRIM);

        // Валидация дат YYYY-MM-DD
        $re_date   = '/^\d{4}-\d{2}-\d{2}$/';
        $date_from = (preg_match($re_date, $date_from_raw)) ? $date_from_raw : '';
        $date_to   = (preg_match($re_date, $date_to_raw))   ? $date_to_raw   : '';
        if ($date_from && !$date_to) { $date_to = $date_from; }
        if ($date_to && !$date_from) { $date_from = $date_to; }

        $table  = 'shop_callrequest_requests';
        $where  = array();
        $params = array('limit' => $limit, 'offset' => $offset);

        // В "Все" скрываем удалённые
        if ($status !== '') {
            $where[] = 'status = s:status';
            $params['status'] = $status;
        } else {
            $where[] = 'status <> s:deleted';
            $params['deleted'] = 'deleted';
        }

        if ($q_name !== '') {
            $where[] = 'name LIKE s:q_name';
            $params['q_name'] = '%'.$q_name.'%';
        }
        if ($q_phone !== '') {
            $where[] = 'phone LIKE s:q_phone';
            $params['q_phone'] = '%'.$q_phone.'%';
        }
        if ($date_from && $date_to) {
            $where[] = 'create_datetime BETWEEN s:df AND s:dt';
            $params['df'] = $date_from.' 00:00:00';
            $params['dt'] = $date_to.' 23:59:59';
        }

        // Новые всегда сверху
        $order = " ORDER BY (status='new') DESC, id DESC";

        $sql = "SELECT * FROM {$table} WHERE ".implode(' AND ', $where)
             . $order . " LIMIT i:limit OFFSET i:offset";
        $items = $m->query($sql, $params)->fetchAll();

        // Подсчёт и границы
        $cnt_sql = "SELECT COUNT(*) FROM {$table} WHERE ".implode(' AND ', $where);
        $total   = (int) $m->query($cnt_sql, $params)->fetchField();
        $pages   = (int) ceil($total / $limit);
        $from    = $total ? ($offset + 1) : 0;
        $to      = min($offset + count($items), $total);

        $this->view->assign(array(
            'items'     => $items,
            'page'      => $page,
            'pages'     => $pages,
            'total'     => $total,
            'from'      => $from,
            'to'        => $to,
            'status'    => $status,
            'q_name'    => $q_name,
            'q_phone'   => $q_phone,
            'date_from' => $date_from_raw,
            'date_to'   => $date_to_raw,
            'csrf'      => (string) waRequest::cookie('_csrf', '')
        ));
    }
}
