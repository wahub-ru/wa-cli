<?php

class shopSeoredirect2PluginRedirectJsonController extends waJsonController
{
	public function execute()
	{
		$method = waRequest::get('method');
		if (!is_string($method))
		{
			throw new waException('', 404);
		}

		$method = strtolower($method);

		if ($method == 'save')
		{
			$this->save();
		}
		elseif ($method === 'sort')
		{
			$this->sort();
		}
		elseif ($method === 'get')
		{
			$this->get();
		}
		elseif ($method === 'getredirect')
		{
			$this->getRedirect();
		}
		elseif ($method === 'delete')
		{
			$this->delete();
		}
		elseif ($method === 'saveredirect')
		{
			$this->saveRedirect();
		}
		elseif ($method === 'deleteallredirects')
		{
			$this->deleteAllRedirects();
		}
	}

	public function save()
	{
		$redirect = waRequest::post('redirect');

		if (!$redirect)
		{
			return;
		}

		$redirect_id = waRequest::post('edit');
		$redirect['id'] = $redirect_id;
		$redirect_model = new shopSeoredirect2RedirectModel();
		$check_redirect = $redirect_model->getByField('url_from', $redirect['url_to']);
		if (!!$check_redirect)
		{
			$this->response = array('url_to' => $check_redirect['url_to']);
			return;
		}
		$redirect_model->addRedirect($redirect);

		$parent_error_id = waRequest::post('error');

		if ($parent_error_id)
		{
			$error_storage = new shopSeoredirect2ErrorStorage();
			$error_storage->deleteById($parent_error_id);
		}
	}

	public function sort()
	{
		$redirect_model = new shopSeoredirect2RedirectModel();
		$data = waRequest::post('data', array());
		//$sort = waRequest::post('sort');

		if (count($data) && is_array($data))
		{
			$redirect_model->newSortingByIds($data);
			$this->response = $data;
		}
		else
		{
			$this->setError('Not data');
		}
	}

	public function get()
	{
		$redirect_model = new shopSeoredirect2RedirectModel();

		$page = max(1, waRequest::get('page', 1));
		$sort = waRequest::get('sort', 'sort');
		$order = waRequest::get('order', 'asc');
		$domain = waRequest::get('domain', null, 'string');
		$query = waRequest::get('query', '', 'string');
		$status = waRequest::get('status', null, 'integer');
		$params = [];
		$where = '';
		$domain = $domain === 'all' ? null : $domain;
		$domain = $domain === 'none' ? '' : $domain;
        $query = $query === '' ? null : $query;
        $status = $status === '' ? null : $status;
        if($domain || $domain === '') {
            $params['domain'] = $domain;
        }
        if($status !== null) {
            $params['status'] = $status;
        }
        if($query) {
            $where .= " 1 AND (url_from LIKE '%$query%' OR url_to LIKE '%$query%')";
        }

		$count_on_page = waRequest::get('count');
        $app_settings_model = new waAppSettingsModel();
        if (!wa_is_int($count_on_page) || !($count_on_page > 0))
        {
            $count_on_page = $app_settings_model->get('shop.seoredirect2', 'count_in_redirect');
        }

		if (!wa_is_int($count_on_page) || !($count_on_page > 0))
		{
			$count_on_page = 20;
		}

		if ($count_on_page < 300)
		{
            $app_settings_model->set('shop.seoredirect2', 'count_in_redirect', $count_on_page);
		}

		$order_query = $this->getOrderQuery($sort, $order);

        if ($params) {
            foreach ($params as $key => $value) {
                if ($where === '') {
                    $where .= ' 1';
                }
                $where .= " AND $key = '$value'";
            }
        }
//		$count_all = $redirect_model->countAll();

        if($where) {
            $redirect_model->where($where);
        }
        $count_all = $redirect_model->countAll();
		$page = shopSeoredirect2Helper::minPage($count_on_page, $page, $count_all);
		$limit = $count_on_page * ($page - 1) . ',' . $count_on_page;

		$redirects = $redirect_model
			->select('*')
            ->where($where)
			->order($order_query)
			->limit($limit)
			->fetchAll();

		$code_http_text = array(301 => '301 - Перемещено навсегда', 302 => '302 - Временно перемещено');
		$domain = wa()->getRouting()->getDomain();
		foreach($redirects as $key => &$redirect)
		{
			// begin create view redirect
			$redirect['view'] = array();
			$redirect['view']['domain'] = $redirect['domain'] == 'general' ? 'Все домены': $redirect['domain'];
			$redirect['view']['code_http'] = $code_http_text[$redirect['code_http']];
			if ($redirect['domain'] != 'general')
			{
				$redirect['view']['url_domain'] = '//' . str_replace('*', '', $redirect['domain']);
			}
			else
			{
				$redirect['view']['url_domain'] = '//' . $domain;
			}

			$redirect['view']['url_from_type'] = shopSeoredirect2Redirect::isReg($redirect['url_from']);
			$redirect['view']['domain_url_from'] = !$redirect['view']['url_from_type'] ?
				$redirect['view']['url_domain'] . $redirect['url_from'] : '';

			$redirect['view']['url_from'] = shopSeoredirect2ViewHelper::truncate($redirect['url_from'], 45, '/.../', true, true);


			$redirect['view']['url_to_type'] = shopSeoredirect2Redirect::isReg($redirect['url_to']);
			if ($redirect['view']['url_to_type'])
			{
				$redirect['view']['domain_url_to'] = '';
			}
			else if (shopSeoredirect2ViewHelper::isURL($redirect['url_to']) === false)
			{
				$redirect['view']['domain_url_to'] = $redirect['view']['url_domain'] . $redirect['url_to'];
			}
			else
			{
				$redirect['view']['domain_url_to'] = $redirect['url_to'];
			}

			$redirect['view']['url_to'] = shopSeoredirect2ViewHelper::truncate($redirect['url_to'], 25, '/.../', true, true);

			$redirect['view']['comment'] = shopSeoredirect2ViewHelper::truncate(strip_tags($redirect['comment']), 25);

			$redirect['view']['edit_datetime'] = shopSeoredirect2ViewHelper::date('humandatetime', $redirect['edit_datetime']);
			$redirect['view']['visit_datetime'] =
                $redirect['visit_datetime'] && $redirect['visit_datetime'] != '0000-00-00 00:00:00'
                    ? shopSeoredirect2ViewHelper::date('humandatetime', $redirect['visit_datetime']) : '';
			// end create view redirect
		}
		unset($redirect);

		$this->response = array(
			'redirects' => $redirects,
			'page' => $page,
			'count_on_page' => $count_on_page,
			'count_all' => $count_all,
			'sorting' => array(
				'sort' => $sort,
				'order' => $order,
			),
		);

	}

	public function getRedirect()
	{
		$redirect_id = waRequest::get('redirect_id');

		$redirect_model = new shopSeoredirect2RedirectModel();
		$redirect = $redirect_model->getById($redirect_id);

		$routing = new shopSeoredirect2WaRouting();
		$domains = $routing->getDomains();
        foreach ($domains as &$domain){
            if(function_exists('idn_to_utf8')) {
                $domain = idn_to_utf8($domain, 0, INTL_IDNA_VARIANT_UTS46);
            }
        }

		$this->response = array(
			'redirect' => $redirect,
			'domains' => $domains,
		);
	}

	public function delete()
	{
		$redirect_id = waRequest::get('redirect_id');
		$redirect_ids = waRequest::post('redirect_ids');

		$redirect_model = new shopSeoredirect2RedirectModel();
		$redirect_model->delete($redirect_id);
		$redirect_model->delete($redirect_ids);
	}

	public function saveRedirect()
	{
		$type = waRequest::post('type');
		$redirect = waRequest::post('redirect');

		$redirect_model = new shopSeoredirect2RedirectModel();
		switch ($type)
		{
			case 'new':
				unset($redirect['id']);
				$redirect_model->addRedirect($redirect);
				break;
			case 'redirect':
				$redirect_model->addRedirect($redirect);
				break;
			case 'error':
				$parent_error_id = $redirect['id'];
				if ($parent_error_id)
				{
					$error_storage = new shopSeoredirect2ErrorStorage();
					$error_storage->deleteById($parent_error_id);
				}
				unset($redirect['id']);
				$redirect_model->addRedirect($redirect);
				break;
		}
	}

	private function deleteAllRedirects()
	{
		$redirect_model = new shopSeoredirect2RedirectModel();

		$redirect_model->deleteAll();
	}

	private function getOrderQuery($sort, $order)
	{
		$sort = trim(strtolower($sort));
		$order = trim(strtolower($order));

		$sort_order = $order === 'desc' ? 'DESC' : 'ASC';

		if ($sort === 'domain')
		{
			return $sort_order === 'ASC'
				? "domain = 'general' DESC, domain ASC"
				: "domain = 'general' ASC, domain DESC";
		}

		$sort_column = null;
		if ($sort === 'sort')
		{
			$sort_column = 'sort';
//			$sort_order = 'ASC';
		}
		elseif ($sort === 'redirect_from')
		{
			$sort_column = 'url_from';
		}
		elseif ($sort === 'redirect_to')
		{
			$sort_column = 'url_to';
		}
		elseif ($sort === 'code_http')
		{
			$sort_column = 'code_http';
		}
		elseif ($sort === 'status')
		{
			$sort_column = 'status';
		}
		elseif ($sort === 'edit_datetime')
		{
			$sort_column = 'edit_datetime';
		}
		elseif ($sort === 'visit_datetime')
		{
			$sort_column = 'visit_datetime';
		}

		return $sort_column === null
			? 'sort ASC'
			: "{$sort_column} {$sort_order}";
	}
}
