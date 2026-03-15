<?php

class shopSeoredirect2PluginAutoredirectJsonController extends waJsonController
{
	public function execute()
	{
		if (method_exists($this, waRequest::get('method')))
		{
			call_user_func(array($this, waRequest::get('method')));
		}
	}

	public function get()
	{
		$shop_urls_model = new shopSeoredirect2ShopUrlsModel();

		$q = waRequest::get('q', '');
		$type = waRequest::get('type');
		$order = waRequest::get('order', 'desc', 'string');
		$sort = waRequest::get('sort', 'create_datetime', 'string');
		$page = max(1, waRequest::get('page', 1));
        $count_on_page = waRequest::get('count');
        $app_settings_model = new waAppSettingsModel();
        if (!wa_is_int($count_on_page) || !($count_on_page > 0))
        {
            $count_on_page = $app_settings_model->get('shop.seoredirect2', 'count_in_auto');
        }

        if (!wa_is_int($count_on_page) || !($count_on_page > 0))
        {
            $count_on_page = 20;
        }

        if ($count_on_page < 300)
        {
            $app_settings_model->set('shop.seoredirect2', 'count_in_auto', $count_on_page);
        }

		$where = '';
		if (!is_null($type) && $type != '' && $type != 'all')
		{
			$where .= "type=" . $type;
		}
		else
		{
			$type = null;
		}
		if (!empty($q))
		{
			$search = empty($where) ? '' : $where . ' AND ';
//			$q_array = explode('/', $q);
//			foreach ($q_array as $key => $url)
//			{
//				if ($url == '')
//				{
//					unset($q_array[$key]);
//					continue;
//				}
//				$q_array[$key] = $shop_urls_model->escape($url);
//			}
//			$search .= "url IN ('" . implode("', '", $q_array) . "')";
            if(preg_match("/^[0-9]+$/", $q)) {
                $search .= "(url LIKE '%$q%' OR full_url LIKE '%$q%' OR id = $q)";
            } else {
                $search .= "(url LIKE '%$q%' OR full_url LIKE '%$q%')";
            }
			$where = $search;

		}

		$count_all = $shop_urls_model->countWhere($where);

		$page = shopSeoredirect2Helper::minPage($count_on_page, $page, $count_all);
		$limit = $count_on_page * ($page - 1) . ',' . $count_on_page;
		$autoredirects = $shop_urls_model->select('*')->order($sort . ' ' . $order)->limit($limit);

		if (!empty($where))
		{
			$autoredirects->where($where);
		}
		$autoredirects = $autoredirects->fetchAll();

		$types = $shop_urls_model->getDataTypes();
		$types_name = array();
		foreach ($types as $type)
		{
			$types_name[$type] = shopSeoredirect2ViewHelper::getNameByType($type);
		}

		foreach ($autoredirects as &$autoredirect)
		{
			$autoredirect['view'] = array(
				'type' => shopSeoredirect2ViewHelper::getNameByType($autoredirect['type']),
				'id' => shopSeoredirect2ViewHelper::getBackendUrlByData($autoredirect),
				'url' => shopSeoredirect2ViewHelper::truncate($autoredirect['url'], 150, '/.../', true, true),
				'full_url' => shopSeoredirect2ViewHelper::truncate($autoredirect['full_url'], 45, '/.../', true, true),
				'parent_id' => shopSeoredirect2ViewHelper::getParentBackendUrlByData($autoredirect),
				'create_datetime' => shopSeoredirect2ViewHelper::date('humandatetime', $autoredirect['create_datetime']),
			);
		}
		unset($autoredirect);

		$this->response = array(
			'autoredirects' => $autoredirects,
			'page' => $page,
			'count_on_page' => $count_on_page,
			'count_all' => $count_all,
			'types' => $types_name,
		);

	}

	public function delete()
	{
		$hash = waRequest::get('hash');
		$hashs = waRequest::post('hashs');

		$shop_urls_model = new shopSeoredirect2ShopUrlsModel();
		$shop_urls_model->delete($hash);
		$shop_urls_model->delete($hashs);
	}

	public function deleteAll()
	{
		$shop_urls_model = new shopSeoredirect2ShopUrlsModel();
		$shop_urls_model->query('TRUNCATE TABLE ' . $shop_urls_model->getTableName());
	}

	public function reinstall()
	{
		$url_archivator = new shopSeoredirect2UrlArchivator();
		$url_archivator->run();
	}
}