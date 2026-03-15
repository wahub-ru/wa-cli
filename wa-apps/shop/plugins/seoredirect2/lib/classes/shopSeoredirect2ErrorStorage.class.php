<?php


class shopSeoredirect2ErrorStorage
{
	private $errors_model;
	private $errors_data_model;
	private $errors_exclude_model;
	
	public function __construct()
	{
		$this->errors_model = new shopSeoredirect2ErrorsModel();
		$this->errors_exclude_model = new shopSeoredirect2ErrorsExcludeModel();
		$this->errors_data_model = new shopSeoredirect2ErrorsDataModel();
	}
	
	public function getAll()
	{
		return $this->getAllIterable()->fetchAll();
	}
	
	public function getAllIterable()
	{
		return $this->errors_model->query("
			select e.*
			from shop_seoredirect2_errors e
			left join shop_seoredirect2_errors_exclude ee
				on e.id = ee.error_id
			where ee.error_id is null
		");
	}
	
	public function getPage($count_on_page, $page, $sort, $order, $domain=null, $query=null)
	{
		$sort = $this->errors_model->escape($sort);
		$order = $this->errors_model->escape($order);
		$domain = $this->errors_model->escape($domain);
        $query = $this->errors_model->escape($query);
		$offset = intval($count_on_page * ($page - 1));
		$limit = intval($count_on_page);
		$where = '';

		if($domain) {
		    $where .= ' AND e.domain = \''.$domain.'\'';
        }
        if($query) {
            $where .= ' AND (e.url LIKE \'%'.$query.'%\' OR ed.ip LIKE \'%'.$query.'%\' OR ed.user_agent LIKE \'%'.$query.
                '%\' OR ed.os LIKE \'%'.$query.'%\' OR ed.browser LIKE \'%'.$query.'%\')';
        }
		
		return $this->errors_model->query("
		select e.*, count(ed.id) as `count`
		from shop_seoredirect2_errors e
		left join shop_seoredirect2_errors_exclude ee
			on e.id = ee.error_id
		left join shop_seoredirect2_errors_data ed
			on e.id = ed.error_id
		where ee.error_id is null
		{$where}
		group by e.id
		order by {$sort} {$order}
		limit {$offset}, {$limit}
		")->fetchAll();
	}

    public function getExcludedPage($count_on_page, $page, $sort, $order, $domain=null, $query=null)
    {
        $sort = $this->errors_model->escape($sort);
        $order = $this->errors_model->escape($order);
        $domain = $this->errors_model->escape($domain);
        $query = $this->errors_model->escape($query);
        $offset = intval($count_on_page * ($page - 1));
        $limit = intval($count_on_page);
        $where = '';

        if($domain) {
            $where .= ' AND e.domain = \''.$domain.'\'';
        }
        if($query) {
            $where .= ' AND e.url LIKE \'%'.$query.'%\'';
        }

        return $this->errors_model->query("
		select e.*
		from shop_seoredirect2_errors e
		left join shop_seoredirect2_errors_exclude ee
			on e.id = ee.error_id
		where ee.error_id is not null
		{$where}
		order by {$sort} {$order}
		limit {$offset}, {$limit}
		")->fetchAll();
    }
	
	public function getById($id)
	{
		return $this->errors_model->getById($id);
	}

    public function getDataByErrorId($id)
    {
        return $this->errors_data_model->getByField('error_id', $id, true);
	}

    public function getCountErrorData($error_id, $query='')
    {
        $where = '';
        if($query != '') {
            $where .= " AND (ip LIKE '%" . $query . "%' OR user_agent LIKE '%"
                . $query . "%' OR os LIKE '%" . $query . "%' OR browser LIKE '%" . $query . "%')";
        }

        $sql = "
      SELECT count(*) cnt
      FROM shop_seoredirect2_errors_data 
      WHERE error_id = {$error_id}
      {$where}
      ";

        return $this->errors_data_model->query($sql)->fetchField();

	}

	public function getDataByFields($count_on_page, $page, $id, $query = '')
    {
        $offset = intval($count_on_page * ($page - 1));
        $limit = intval($count_on_page);
        $where = '';
        if($query != '') {
            $where .= " AND (ip LIKE '%" . $query . "%' OR user_agent LIKE '%"
                . $query . "%' OR os LIKE '%" . $query . "%' OR browser LIKE '%" . $query . "%')";
        }

        $sql = "
      SELECT * 
      FROM shop_seoredirect2_errors_data 
      WHERE error_id = {$id}
      {$where}     
		limit {$offset}, {$limit}
      ";

        return $this->errors_data_model->query($sql)->fetchAll();
	}
	
	public function getCount($domain=null, $query=null)
	{
        $domain = $this->errors_model->escape($domain);
        $query = $this->errors_model->escape($query);
        $where = '';

        if($domain) {
            $where .= ' AND e.domain = \''.$domain.'\'';
        }
        if($query) {
            $where .= ' AND (e.url LIKE \'%'.$query.'%\' OR ed.ip LIKE \'%'.$query.'%\' OR ed.user_agent LIKE \'%'.$query.
                '%\' OR ed.os LIKE \'%'.$query.'%\' OR ed.browser LIKE \'%'.$query.'%\')';
        }

		return $this->errors_model->query("
		select count(DISTINCT(e.id)) cnt
		from shop_seoredirect2_errors e
		left join shop_seoredirect2_errors_exclude ee
			on e.id = ee.error_id
		left join shop_seoredirect2_errors_data ed
			on e.id = ed.error_id
		where ee.error_id is null
		{$where}		
		")->fetchField();
	}

    public function getExcludedCount($domain=null, $query=null)
    {
        $domain = $this->errors_model->escape($domain);
        $query = $this->errors_model->escape($query);
        $where = '';

        if($domain) {
            $where .= ' AND e.domain = \''.$domain.'\'';
        }
        if($query) {
            $where .= ' AND e.url LIKE \'%'.$query.'%\'';
        }

        return $this->errors_model->query("
		select count(*) cnt
		from shop_seoredirect2_errors e
		left join shop_seoredirect2_errors_exclude ee
			on e.id = ee.error_id
		where ee.error_id is not null
		{$where}
		")->fetchField();
    }
	
	public function addError($domain, $url, $error_code)
	{
		$error_id = $this->errors_model->addError($domain, $url, $error_code);
        $plugin = shopSeoredirect2Plugin::getInstance();
        $settings = $plugin->getSettings();
		if($error_id && $settings['errors_data']) {
            $this->errors_data_model->addData($domain, $url, $error_id);
        }
	}
	
	public function deleteById($id)
	{
		$this->errors_model->deleteById($id);
	}

	public function deleteDataById($id)
	{
		$this->errors_data_model->deleteById($id);
	}

	public function deleteByIds($ids)
	{
		$this->errors_model->deleteByField('id', $ids);
	}

	public function deleteDataByIds($ids)
	{
		$this->errors_data_model->deleteByField('id', $ids);
	}

	public function deleteDataByErrorId($id)
	{
		$this->errors_data_model->deleteByField('error_id', $id);
	}

	public function deleteExcludedByIds($ids)
	{
		$this->errors_exclude_model->deleteByField('error_id', $ids);
	}
	
	public function addExclude($id)
	{
		$this->errors_exclude_model->replace(array(
			'error_id' => $id
		));
	}
	
	public function clean()
	{
		$this->errors_model->truncate();
		$this->errors_exclude_model->truncate();
	}

	public function cleanData()
	{
		$this->errors_data_model->truncate();
	}
	
	public function cleanExcluded()
	{
		$_ids = $this->errors_exclude_model->getAll('error_id');
		$ids = array_keys($_ids);
		$this->deleteByIds($ids);
		
		$this->errors_exclude_model->truncate();
	}

    public function cleanErrorsByTime($days, $data_days)
    {
        $date = date('Y-m-d H:i:s', time() - (int)$days * 24 * 60 * 60);
        $_ids = $this->errors_model->where('edit_datetime < ?', $date)->limit(5000)->fetchAll('id');
        $ids = array_keys($_ids);
        $this->deleteByIds($ids);
        $this->deleteExcludedByIds($ids);
        $shop_urls_model = new shopSeoredirect2ShopUrlsModel();
        if(count($ids) === 0) {
            if($data_days === 'never') {
                return;
            }
            $date = date('Y-m-d H:i:s', time() - (int)$data_days * 24 * 60 * 60);
            $_ids = $this->errors_data_model->where('create_datetime < ?', $date)->limit(5000)->fetchAll('id');
            $ids = array_keys($_ids);
            $this->deleteDataByIds($ids);
        }
//        $shop_urls_model->cleanUrls();

	}
}
