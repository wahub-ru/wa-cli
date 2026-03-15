<?php

class shopSeoredirect2ShopUrlGenerator
{
	public $domain_url;
	public $route_url;
	public $url_type;
	// TODO: создать свойства класса $type и $id
	// TODO: создать метод getParentUrl()
	public function __construct($domain_url = null, $route_url = null, $url_type = null)
	{
		if ($domain_url != null)
		{
			$this->domain_url = $domain_url;
		}
		if ($route_url != null)
		{
			$this->route_url = $route_url;
		}

		$this->url_type = $url_type !== null ? intval($url_type) : (int)waRequest::param('url_type');
	}

	public function getUrl(shopSeoredirect2Autoredirect $item)
	{
		switch ($item->getType())
		{
			case shopSeoredirect2Type::CATEGORY:
				return $item->canBeCatalogreviewsUrl()
					? $this->getCatalogreviewsUrl($item->getId())
					: $this->getCategoryUrl($item->getId());
			case shopSeoredirect2Type::PRODUCT:
				return $this->getProductUrl($item->getId());
			case shopSeoredirect2Type::PRODUCT_PAGE:
				return $this->getProductPageUrl($item->getId());
			case shopSeoredirect2Type::PAGE:
				return $this->getPageUrl($item->getId());
			case shopSeoredirect2Type::SEOFILTER:
				return $item->canBeCatalogreviewsUrl()
					? $this->getCatalogreviewsUrl($item->getParentId(), $item->getId())
					: $this->getSeofilterUrl($item->getId(), $item->getParentId());
			case shopSeoredirect2Type::PRODUCT_REVIEWS:
				return $this->getProductReviewsUrl($item->getId());
			case shopSeoredirect2Type::PRODUCTBRANDS:
				return $this->getProductbrandsBrandUrl($item->getId());
			case shopSeoredirect2Type::PRODUCTBRANDS_CATEGORY:
				return $this->getProductbrandsBrandCategoryUrl($item->getId(), $item->getParentId());
			default:
				return null;
		}
	}

	private function getCategoryUrl($id)
	{
		$category_model = new shopCategoryModel();
		$category = $category_model->getById($id);
		if (empty($category))
		{
			return null;
		}

		return wa()->getRouting()->getUrl(
			'shop/frontend/category',
			array(
				'category_url' => ($this->url_type == shopSeoredirect2UrlType::FLAT)
					? $category['url']
					: $category['full_url'],
			),
			true,
			$this->domain_url,
			$this->route_url
		);
	}

	/**
	 * В зависимости от url_type возвращает url категории
	 *
	 * @param $id
	 * @return null|string
	 */
	private function getCatUrl($id)
	{
		$category_model = new shopCategoryModel();
		$category = $category_model->getById($id);
		if (empty($category))
		{
			return null;
		}
		if ($this->url_type == shopSeoredirect2UrlType::FLAT)
		{
			return $category['url'];
		}
		else
		{
			return $category['full_url'];
		}
	}

	private function getProductParams($id)
	{
		$product_model = new shopProductModel();
		$product = $product_model->getById($id);
		if ($product == null)
		{
			return null;
		}
		$params = array('product_url' => $product["url"]);
		if ($product["category_id"])
		{
			$category_url = $this->getCatUrl($product["category_id"]);
			if ($category_url)
			{
				$params['category_url'] = $category_url;
			}
		}

		return $params;
	}

	private function getProductUrl($id)
	{
		$params = $this->getProductParams($id);
		
		if (!isset($params))
		{
			return null;
		}

		return wa()->getRouting()->getUrl('shop/frontend/product', $params, true, $this->domain_url, $this->route_url);
	}

	private function getProductPageUrl($id)
	{
		$pages_model = new shopProductPagesModel();
		$page = $pages_model->get($id);
		$params = $this->getProductParams($page['product_id']);

		if (!$page || $params === null)
		{
			return null;
		}

		$params['page_url'] = $page['url'];

		return wa()->getRouting()->getUrl(
			'shop/frontend/productPage',
			$params,
			true,
			$this->domain_url,
			$this->route_url
		);
	}

	private function getPageUrl($id)
	{
		$page_model = new shopPageModel();
		$page = $page_model->get($id);
		if (empty($page))
		{
			return null;
		}
		if ($this->domain_url != null && $this->route_url != null)
		{
			if ($page['domain'] != $this->domain_url || $page['route'] != $this->route_url)
			{
				return null;
			}
		}
		$url = wa()->getRouting()->getUrl('/frontend', array(), true, $this->domain_url, $this->route_url);
		$url .= $page['full_url'];

		return $url;
	}

	private function getSeofilterUrl($filter_id, $category_id)
	{
		if (!shopSeoredirect2Helper::seofilterOver2())
		{
			return null;
		}

		$ar_filter = new shopSeofilterFilter();
		$filter = $ar_filter->getById($filter_id);

		if (!isset($filter))
		{
			return null;
		}

		$routing = new shopSeoredirect2WaRouting();
		$storefront = $routing->getCurrentStorefront();
		$is_enabled = $filter->is_enabled && shopSeoredirect2SeofilterFilterExtension::isAppliedToStorefrontCategory(
			$filter,
			$storefront,
			$category_id
		);

		return $is_enabled ? shopSeofilterViewHelper::getFrontendCategoryUrl($filter_id, $category_id) : null;
	}

	private function getProductReviewsUrl($id)
	{
		$url = $this->getProductUrl($id);
		
		if ($url == null)
		{
			return null;
		}
		
		return $url . 'reviews/';
	}

	private function getProductbrandsBrandUrl($id)
	{
		$brands = shopProductbrandsPlugin::getBrands();

		foreach ($brands as $brand)
		{
			if ($brand['id'] == $id)
			{
				return $brand['url'];
			}
		}

		return null;
	}

	private function getProductbrandsBrandCategoryUrl($category_id, $brand_id)
	{
		if (shopSeoredirect2Brands::hasBrandCategory($brand_id, $category_id))
		{
			$category_url = $this->getCatUrl($category_id);
			if ($category_url)
			{
				return $this->getProductbrandsBrandUrl($brand_id) . $category_url . '/';
			}
		}

		return null;
	}

	private function getCatalogreviewsUrl($category_id, $seofilter_id = null)
	{
		$catalogreviews_helper = shopSeoredirect2CatalogreviewsPluginHelper::getInstance();
		$seofilter_plugin_helper = shopSeoredirect2SeofilterPluginHelper::getInstance();

		$catalogreviews_plugin_env = $catalogreviews_helper->getPluginEnv();
		if (!$catalogreviews_plugin_env)
		{
			return null;
		}

		if ($seofilter_id > 0 && !$seofilter_plugin_helper->isPluginInstalled())
		{
			return null;
		}

		$category_model = new shopCategoryModel();
		$category = $category_model->getById($category_id);
		if (!$category)
		{
			return null;
		}

		$seofilter_filter = null;
		if ($seofilter_id > 0)
		{
			$filters_frontend_storage = $seofilter_plugin_helper->getFilterStorage();
			$seofilter_filter = $filters_frontend_storage->getById($seofilter_id);
		}


		$plugin_routing = $catalogreviews_plugin_env->getPluginRouting();

		return $seofilter_filter
			? $plugin_routing->getSeofilterReviewsPageUrl($category, $seofilter_filter)
			: $plugin_routing->getReviewsPageUrl($category);
	}
}
