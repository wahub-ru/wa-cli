<?php

class shopSeoredirect2ShopRouting extends shopSeoredirect2Routing
{
	public function __construct()
	{
		$shop_routing_path = wa()->getAppPath('lib/config/routing.php', 'shop');
		parent::__construct($shop_routing_path);
	}

	/**
	 * Возвращает шаблон роутинга для категории.<br>
	 * Например: <code>'category/<category_url>/'</code>
	 *
	 * @param null|integer $url_type
	 * @return string|null
	 */
	public function getCategory($url_type = null)
	{
		$arr = $this->getKeysByValues($url_type, 'frontend/category');

		return ifempty($arr[0]);
	}

	/**
	 * Возвращает шаблон роутинга для товара.<br>
	 * Например: <code>'product/<product_url:[^/]+>/'</code>
	 *
	 * @param null|integer $url_type
	 * @return string|null
	 */
	public function getProduct($url_type = null)
	{
		$arr = $this->getKeysByValues($url_type, 'frontend/product');

		return ifempty($arr[0]);
	}

	/**
	 * Возвращает шаблон роутинга для отзывов на товар.<br>
	 * Например: <code>'product/<product_url:[^/]+>/reviews/'</code>
	 *
	 * @param null|integer $url_type
	 * @return string|null
	 */
	public function getProductReviews($url_type = null)
	{
		$arr = $this->getKeysByValues($url_type, 'frontend/productReviews');

		return ifempty($arr[0]);
	}

	/**
	 * Возвращает шаблон роутинга для страниц товара.<br>
	 * Например: <code>'product/<product_url:[^/]+>/<page_url>/'</code>
	 *
	 * @param null|integer $url_type
	 * @return string|null
	 */
	public function getProductPage($url_type = null)
	{
		$arr = $this->getKeysByValues($url_type, 'frontend/productPage');

		return ifempty($arr[0]);
	}

	/**
	 * Возвращает шаблоны роутинга для заданного action`а или -ов.<br>
	 * Например: <code>[0 => 'product/<product_url:[^/]+>/<page_url>/']</code>
	 *
	 * @param integer $url_type
	 * @param string|null $search_value 'frontend/category'
	 * @return array
	 */
	public function getKeysByValues($url_type, $search_value = null)
	{
		$url_type = $this->getUrlType($url_type);

		return array_keys($this->routing[$url_type], $search_value);
	}

	public function getUrlType(&$url_type = null)
	{
		if ($url_type === null && waRequest::param('url_type') !== null)
		{
			$url_type = waRequest::param('url_type');
		}
		else
		{
			$url_type = shopSeoredirect2UrlType::FLAT;
		}

		return $url_type;
	}

	/**
	 * Возвращает первую часть шаблона<br>
	 * Например: <br>
	 * param <code>'category/<category_url>/'</code><br>
	 *
	 * @param string $frontend
	 * @return string|null 'category'
	 */
	protected function getFrontend($frontend)
	{
		if (empty($frontend))
		{
			return null;
		}
		$path_array = explode('/', $frontend);

		return array_shift($path_array);
	}

	/**
	 * @param $url_type
	 * @return null|string 'category'
	 */
	public function getFrontendCategory($url_type)
	{
		if ($url_type == shopSeoredirect2UrlType::FLAT || $url_type == shopSeoredirect2UrlType::MIXED)
		{
			return $this->getFrontend($this->getCategory($url_type));
		}

		return null;
	}

	/**
	 * @param $url_type
	 * @return null|string 'product'
	 */
	public function getFrontendProduct($url_type)
	{
		if ($url_type == shopSeoredirect2UrlType::FLAT)
		{
			return $this->getFrontend($this->getProduct($url_type));
		}

		return null;
	}

	/**
	 * @param $url_type
	 * @return null|string 'product'
	 */
	public function getFrontendProductPage($url_type)
	{
		if ($url_type == shopSeoredirect2UrlType::FLAT)
		{
			return $this->getFrontend($this->getProductPage($url_type));
		}

		return null;
	}

	/**
	 * @param $url_type
	 * @return null|string 'product'
	 */
	public function getFrontendProductReviews($url_type)
	{
		if ($url_type == shopSeoredirect2UrlType::FLAT)
		{
			return $this->getFrontend($this->getProductReviews($url_type));
		}

		return null;
	}
}