<?php

class shopSeoredirect2ShopUrlTypeModel extends waModel
{
	protected $table = 'shop_seoredirect2_shop_url_type';
	protected $cache;

	public function addData($domain, $route_url, $url_type)
	{
		$data = array(
			'domain' => $domain,
			'route' => $route_url,
			'url_type' => $url_type,
			'edit_datetime' => date('Y-m-d H:i:s')
		);

		if ($this->hasUrlType($data))
		{
			return;
		}

		$this->insert($data, 1);
		$this->getCache()->delete();
	}

	public function newCache()
	{
		return new waSerializeCache('url_types', -1, 'shop_seoredirect2');
	}

	public function getCache()
	{
		if (is_null($this->cache))
		{
			return $this->newCache();
		}
		return $this->cache;
	}

	public function getUrlTypes()
	{
		if ($this->getCache()->isCached())
		{
			return $this->getCache()->get();
		}
		$url_types = $this->getAll();
		$this->getCache()->set($url_types);

		return $url_types;
	}

	public function hasUrlType($data)
	{
		$url_types = $this->getUrlTypes();
		foreach ($url_types as $url_type)
		{
			if (self::equal($data, $url_type))
			{
				return true;
			}
		}

		return false;
	}

	public static function equal($data1, $data2)
	{
		if (
			$data1['domain'] == $data2['domain'] &&
			$data1['route'] == $data2['route'] &&
			$data1['url_type'] == $data2['url_type']
		)
		{
			return true;
		}

		return false;
	}
}