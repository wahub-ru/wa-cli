<?php

class shopSeoredirect2BrandsUrl
{
	private $status;
	private $url;
	private $url_array;

	public function __construct()
	{
		$this->status = wa()->getResponse()->getStatus();
		$this->url = wa()->getRouting()->getCurrentUrl();

		$url_array = explode('/', $this->url);
		$this->url_array = array_filter($url_array, array($this, 'isValueEmpty'));
	}

	public function isBrandsPage()
	{
		return waRequest::param('plugin') == 'productbrands' || $this->isUrlBrands();
	}

	public function statusOk()
	{
		return $this->status === null || $this->status == 200;
	}

	public function isUrlBrands()
	{
		return (!empty($this->url_array[0]) ? $this->url_array[0] == 'brand' : false) && count($this->url_array) >= 2;
 	}

	public function getBrandUrl()
	{
		if (!$this->isUrlBrands())
		{
			return '';
		}

		return !empty($this->url_array[1]) ? $this->url_array[1] : '';
	}

	public function hasBrandCategoryUrl()
	{
		return $this->isUrlBrands() && count($this->url_array) > 2;
	}

	public function getBrandCategoryUrl()
	{
		if (!$this->hasBrandCategoryUrl())
		{
			return '';
		}

		$urls = $this->url_array;

		unset($urls[0]);
		unset($urls[1]);

		return implode('/', $urls);
	}

	private function isValueEmpty($value)
	{
		return $value !== "";
	}
}