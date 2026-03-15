<?php

class shopSeoredirect2PluginErrorJsonController extends waJsonController
{
	public function execute()
	{
		$method = waRequest::get('method');
		if (is_string($method))
		{
			$method = strtolower($method);
		}
		
		if ($method === 'get')
		{
			$this->get();
		}
        elseif ($method === 'getexcluded')
        {
            $this->getExcluded();
        }
		elseif ($method === 'geterrorbyid')
		{
			$this->getErrorById();
		}
		elseif ($method === 'geterrordata')
		{
			$this->getErrorData();
		}
		elseif ($method === 'delete')
		{
			$this->delete();
		}
		elseif ($method === 'deletedata')
		{
			$this->deleteData();
		}
		elseif ($method === 'exclude')
		{
			$this->exclude();
		}
		elseif ($method === 'unexclude')
		{
			$this->unexclude();
		}
		elseif ($method === 'deleteallerrors')
		{
			$this->deleteAllErrors();
		}
		elseif ($method === 'deleteallerrordata')
		{
			$this->deleteAllErrorData();
		}
		elseif ($method === 'deleteallerrorsdata')
		{
			$this->deleteAllErrorsData();
		}
        elseif ($method === 'deleteexcludederrors')
		{
			$this->deleteExcludedErrors();
		}
		else
		{
			$this->errors[] = 'Unknown method';
		}
	}

	public function get()
	{
		$order = waRequest::get('order', 'desc', 'string');
		$sort = waRequest::get('sort', 'edit_datetime', 'string');
		$domain = waRequest::get('domain', 'all', 'string');
		$query = waRequest::get('query', '', 'string');
		$page = max(1, waRequest::get('page', 1));
		$domain = $domain === 'all' ? null : $domain;
        $query = $query === '' ? null : $query;

		$count_on_page = waRequest::get('count');
        $app_settings_model = new waAppSettingsModel();
        if (!wa_is_int($count_on_page) || !($count_on_page > 0))
        {
            $count_on_page = $app_settings_model->get('shop.seoredirect2', 'count_in_error');
        }

		if (!wa_is_int($count_on_page) || !($count_on_page > 0))
		{
			$count_on_page = 20;
		}

		if ($count_on_page < 300)
		{
            $app_settings_model->set('shop.seoredirect2', 'count_in_error', $count_on_page);
		}

		$error_storage = new shopSeoredirect2ErrorStorage();
		$count_all = $error_storage->getCount($domain, $query);
		$page = shopSeoredirect2Helper::minPage($count_on_page, $page, $count_all);
		$errors  = $error_storage->getPage($count_on_page, $page, $sort, $order, $domain, $query);

		foreach ($errors as &$error)
		{
			$error['view'] = array();
			$error['view']['url_domain'] = '//' . str_replace('*', '', $error['domain']);
			$error['view']['url'] = shopSeoredirect2ViewHelper::truncate($error['url'], 45, '/.../', true, true);
			$error['view']['http_referer'] = shopSeoredirect2ViewHelper::truncate($error['http_referer'], 25, '/.../', true, true);
			$error['view']['edit_datetime'] = shopSeoredirect2ViewHelper::date('humandatetime', $error['edit_datetime']);
			$error['view']['create_datetime'] = shopSeoredirect2ViewHelper::date('humandatetime', $error['create_datetime']);
		}

        $plugin = shopSeoredirect2Plugin::getInstance();
        $settings= $plugin->getSettings();
        $errors_data = ifset($settings['errors_data'], false);

		$this->response = array(
			'errors' => $errors,
			'page' => $page,
			'count_on_page' => $count_on_page,
			'count_all' => $count_all,
			'errors_data' => $errors_data,
			'sorting' => array(
				'sort' => $sort,
				'order' => $order,
			),
		);

	}

    public function getExcluded()
    {
        $order = waRequest::get('order', 'desc', 'string');
        $sort = waRequest::get('sort', 'edit_datetime', 'string');
        $domain = waRequest::get('domain', 'all', 'string');
        $query = waRequest::get('query', '', 'string');
        $page = max(1, waRequest::get('page', 1));
        $domain = $domain === 'all' ? null : $domain;
        $query = $query === '' ? null : $query;

        $count_on_page = waRequest::get('count');
        if (!wa_is_int($count_on_page) || !($count_on_page > 0))
        {
            $count_on_page = wa()->getStorage()->get('shop/seoredirect2/count_on_page');
        }

        if (!wa_is_int($count_on_page) || !($count_on_page > 0))
        {
            $count_on_page = 20;
        }

        if ($count_on_page < 200)
        {
            wa()->getStorage()->set('shop/seoredirect2/count_on_page', $count_on_page);
        }

        $error_storage = new shopSeoredirect2ErrorStorage();
        $count_all = $error_storage->getExcludedCount($domain, $query);
        $page = shopSeoredirect2Helper::minPage($count_on_page, $page, $count_all);
        $errors  = $error_storage->getExcludedPage($count_on_page, $page, $sort, $order, $domain, $query);

        foreach ($errors as &$error)
        {
            $error['view'] = array();
            $error['view']['url_domain'] = '//' . str_replace('*', '', $error['domain']);
            $error['view']['url'] = shopSeoredirect2ViewHelper::truncate($error['url'], 45, '/.../', true, true);
            $error['view']['http_referer'] = shopSeoredirect2ViewHelper::truncate($error['http_referer'], 25, '/.../', true, true);
            $error['view']['edit_datetime'] = shopSeoredirect2ViewHelper::date('humandatetime', $error['edit_datetime']);
            $error['view']['create_datetime'] = shopSeoredirect2ViewHelper::date('humandatetime', $error['create_datetime']);
        }

        $this->response = array(
            'errors' => $errors,
            'page' => $page,
            'count_on_page' => $count_on_page,
            'count_all' => $count_all,
            'sorting' => array(
                'sort' => $sort,
                'order' => $order,
            ),
        );

    }

	public function getErrorById()
	{
		$error_id = waRequest::get('error_id');

		$error_storage = new shopSeoredirect2ErrorStorage();
		$error = $error_storage->getById($error_id);

		$routing = new shopSeoredirect2WaRouting();
		$domains = $routing->getDomains();

		$this->response = array(
			'error' => $error,
			'domains' => $domains,
		);
	}

    public function getErrorData()
    {
        $error_id = waRequest::get('error_id');

        $query = waRequest::get('query', '', 'string');
        $page = max(1, waRequest::get('page', 1));
        $query = $query === '' ? null : $query;

        $count_on_page = waRequest::get('count');
        if (!wa_is_int($count_on_page) || !($count_on_page > 0))
        {
            $count_on_page = wa()->getStorage()->get('shop/seoredirect2/count_on_page');
        }

        if (!wa_is_int($count_on_page) || !($count_on_page > 0))
        {
            $count_on_page = 20;
        }

        if ($count_on_page < 200)
        {
            wa()->getStorage()->set('shop/seoredirect2/count_on_page', $count_on_page);
        }

        $error_storage = new shopSeoredirect2ErrorStorage();
        $count_all = $error_storage->getCountErrorData($error_id, $query);
        $page = shopSeoredirect2Helper::minPage($count_on_page, $page, $count_all);

        $error_data = $error_storage->getDataByFields($count_on_page, $page, $error_id, $query);



        $this->response = array(
            'errorData' => $error_data,
            'page' => $page,
            'count_on_page' => $count_on_page,
            'count_all' => $count_all,
        );
    }

	public function delete()
	{
		$error_id = waRequest::get('error_id');
		$error_storage = new shopSeoredirect2ErrorStorage();
		
		if ($error_id)
		{
			$error_storage->deleteById($error_id);
			
			return;
		}
		
		
		$error_ids = waRequest::post('error_ids');
		
		if (!is_array($error_ids) || count($error_ids) == 0)
		{
			return;
		}
		
		$error_storage->deleteByIds($error_ids);
	}

	public function deleteData()
	{
		$error_id = waRequest::get('id');
		$error_storage = new shopSeoredirect2ErrorStorage();

		if ($error_id)
		{
			$error_storage->deleteDataById($error_id);

			return;
		}


		$error_ids = waRequest::post('ids');

		if (!is_array($error_ids) || count($error_ids) == 0)
		{
			return;
		}

		$error_storage->deleteDataByIds($error_ids);
	}
	
	public function exclude()
	{
		$ids = waRequest::post('ids');
		
		if (!is_array($ids) || count($ids) == 0)
		{
			return;
		}
		
		$error_storage = new shopSeoredirect2ErrorStorage();
		
		foreach ($ids as $id)
		{
			$error_storage->addExclude($id);
		}
	}

    public function unexclude()
    {
        $ids = waRequest::post('ids');

        if (!is_array($ids) || count($ids) == 0)
        {
            return;
        }

        $error_storage = new shopSeoredirect2ErrorStorage();

        $error_storage->deleteExcludedByIds($ids);
    }

	private function deleteAllErrors()
	{
		$error_storage = new shopSeoredirect2ErrorStorage();

		$error_storage->clean();
	}

	private function deleteAllErrorsData()
	{
		$error_storage = new shopSeoredirect2ErrorStorage();

		$error_storage->cleanData();
	}

	private function deleteAllErrorData()
	{
        $error_id = waRequest::get('error_id');
        $error_storage = new shopSeoredirect2ErrorStorage();

        if ($error_id)
        {
            $error_storage->deleteDataByErrorId($error_id);

            return;
        }
	}

	private function deleteExcludedErrors()
	{
		$error_storage = new shopSeoredirect2ErrorStorage();

		$error_storage->cleanExcluded();
	}
}
