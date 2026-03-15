<?php

class shopSeoredirect2ShopUrlsModel extends waModel
{
	const SEOFILTER_URL_TYPE_CATEGORY_JOIN = '3';

	protected $table = 'shop_seoredirect2_shop_urls';

	public function addCategoryData($category)
	{
        if(!$category['url']) {
            return;
        }

		if ($category['parent_id'])
		{
			$category_model = new shopCategoryModel();
			$parent_category = $category_model->getById($category['parent_id']);

			if ($parent_category)
			{
				$this->addCategoryData($parent_category);
			}
		}

		$this->addSeofilterCategoryData($category);

		$data = array(
			'id' => $category['id'],
			'type' => shopSeoredirect2Type::CATEGORY,
			'url' => $category['url'],
			'full_url' => $category['full_url'],
			'parent_id' => (int)$category['parent_id'],
			'create_datetime' => date('Y-m-d H:i:s'),
		);

		$this->addData($data);
	}

	public function addProductData($product)
	{
        if(!$product['url']) {
            return;
        }

		$data = array(
			'id' => $product['id'],
			'type' => shopSeoredirect2Type::PRODUCT,
			'url' => $product['url'],
			'full_url' => '',
			'parent_id' => intval($product['category_id']),
			'create_datetime' => date('Y-m-d H:i:s'),
		);

		$this->addData($data);

		if ($product['category_id'])
		{
			$category_model = new shopCategoryModel();
			$category = $category_model->getById($product['category_id']);
			$this->addCategoryData($category);
		}

		foreach ($product['pages'] as $page)
		{
			$this->addProductPageData($product, $page);
		}
	}

	public function addProductPageData($product, $page)
	{
        if(!$page['url']) {
            return;
        }

		$data = array(
			'id' => $page['id'],
			'type' => shopSeoredirect2Type::PRODUCT_PAGE,
			'url' => $page['url'],
			'full_url' => '',
			'parent_id' => (int)$product['id'],
			'create_datetime' => date('Y-m-d H:i:s'),
		);

		$this->addData($data);
	}

	public function addPageData($page)
	{
        if(!$page['url']) {
            return;
        }

		$data = array(
			'id' => $page['id'],
			'type' => shopSeoredirect2Type::PAGE,
			'url' => preg_replace('/\/$/', '', $page['url']),
			'full_url' => preg_replace('/\/$/', '', $page['full_url']),
			'parent_id' => (int)$page['parent_id'],
			'create_datetime' => date('Y-m-d H:i:s'),
		);

		$this->addData($data);
	}

	public function addSeofilterData($filter)
	{
        if(!$filter['url']) {
            return;
        }

		$new_url = $filter['url'];
		$category_id = intval($filter['category_id']);

		if ($category_id === 0)
		{
			$sql = '
SELECT *
FROM `shop_seoredirect2_shop_urls`
WHERE id = :filter_id AND type = :type
GROUP BY parent_id
';

			$query_params = array(
				'filter_id' => $filter['id'],
				'type' => shopSeoredirect2Type::SEOFILTER,
			);

			$existing_filter_urls = $this->query($sql, $query_params);

			foreach ($existing_filter_urls as $existing_filter_url)
			{
				if ($existing_filter_url['url'] !== $filter['url'])
				{
					if ($existing_filter_url['url'] == $existing_filter_url['full_url'])
					{
						$new_full_url = $new_url;
					}
					else
					{
						$category_url = substr($existing_filter_url['full_url'], 0, strlen($existing_filter_url['full_url']) - strlen($existing_filter_url['url']) - 1);

						$new_full_url = "{$category_url}-{$new_url}";
					}

					$updated_url = array(
						'id' => $existing_filter_url['id'],
						'type' => shopSeoredirect2Type::SEOFILTER,
						'url' => $new_url,
						'full_url' => $new_full_url,
						'parent_id' => intval($existing_filter_url['parent_id']),
						'create_datetime' => date('Y-m-d H:i:s'),
					);

					$this->addData($updated_url);
				}
			}
		}

		$data = array(
			'id' => $filter['id'],
			'type' => shopSeoredirect2Type::SEOFILTER,
			'url' => $filter['url'],
			'full_url' => $filter['full_url'],
			'parent_id' => $category_id,
			'create_datetime' => date('Y-m-d H:i:s'),
		);

		$this->addData($data);
	}

	public function addBrandData($brand)
	{
        if(!$brand['url']) {
            return;
        }

		$data = array(
			'id' => $brand['id'],
			'type' => shopSeoredirect2Type::PRODUCTBRANDS,
			'url' => $brand['url'],
			'full_url' => '',
			'parent_id' => 0,
			'create_datetime' => date('Y-m-d H:i:s'),
		);

		$this->addData($data);
	}

	public function addBrandCategoryData($brandcategory)
	{
		$data = array(
			'id' => $brandcategory['id'],
			'type' => shopSeoredirect2Type::PRODUCTBRANDS_CATEGORY,
			'url' => $brandcategory['url'],
			'full_url' => $brandcategory['full_url'],
			'parent_id' => $brandcategory['parent_id'],
			'create_datetime' => date('Y-m-d H:i:s'),
		);

		$this->addData($data);
	}

	/**
	 * @param string $full_url
	 * @param array $category
	 * @param shopSeofilterFilter|null $seofilter
	 */
	public function addCatalogreviewsData($full_url, $category, $seofilter)
	{
		$this->addCategoryData($category);

		if ($seofilter)
		{
			$this->addSeofilterData(array(
				'id' => $seofilter->id,
				'url' => $seofilter->url,
				'full_url' => $seofilter->getFrontendCategoryUrl($category),
				'category_id' => $category['id'],
			));
		}
	}

	private function addData($data)
	{
		$data['hash'] = $this->getHash($data);
		$this->delete($data['hash']);
		$this->insert($data, waModel::INSERT_IGNORE);
	}

	private function getHash($node)
	{
		$hash_array = array(
			'id' => $node['id'],
			'type' => $node['type'],
			'url' => $node['url'],
			'full_url' => $node['full_url'],
			'parent_id' => $node['parent_id'],
		);
		
		return md5(json_encode($hash_array));
	}

	public function getDataTypes()
	{
		return array_keys($this->select('DISTINCT type')->fetchAll('type', true));
	}

	public function countWhere($where)
	{
		if (is_null($where) || $where == '')
		{
			$countAll = $this->countAll();

			return $countAll;
		}
		else
		{
			$field = $this->select('COUNT(*)')->where($where)->fetchField();

			return $field;
		}
	}

	public function getByUrl($url_variants, &$can_be_catalogreviews = false)
	{
		$can_be_catalogreviews = $this->canBeCatalogreviews($url_variants);

		/**
		 * специальный костыль для обработки записей плагина seofilter, сделанных при типах url CATEGORY_JOIN
		 * ищем по колонке full_url вместо url (в url записанно <filter_url>, в full_url записанно <category_url>-<filter_url>)
		 * для найденных - добавляем в поиск category_url, чтобы найти категорию фильтра
		 *
		 * результаты поисков объединяем
		 */
		$seofilter_joined_urls = array();
		if (count($url_variants) > 0)
		{
			$url = $url_variants[count($url_variants) - 1];
			$seofilter_joined_urls = $this->select('*')
				->where('type = :type_seofilter', array('type_seofilter' => shopSeoredirect2Type::SEOFILTER))
				->where('full_url = :full_url', array('full_url' => $url))
				->where('url != :url', array('url' => $url))
				->order('id DESC')
				->fetchAll();

			foreach ($seofilter_joined_urls as $seofilter_joined_url)
			{
				$url_variants[] = substr($seofilter_joined_url['full_url'], 0, strlen($seofilter_joined_url['full_url']) - 1 - strlen($seofilter_joined_url['url']));
			}
		}

		$all_urls = array();

		if (count($url_variants) > 0)
		{
			$all_urls = $this
				->select('*')
				->where('url IN (:urls)', array('urls' => $url_variants))
				->order('id DESC')
				->fetchAll();
		}

		$urls = array_merge($all_urls, $seofilter_joined_urls);

		if (count($urls) === 0)
		{
			return array();
		}

		$domain = wa()->getRouting()->getDomain();
		$route = wa()->getRouting()->getRoute();
		$seoredirect2_domain_model = new shopSeoredirect2ShopUrlTypeModel();
		$url_types = $seoredirect2_domain_model->getByField(
			array(
				'domain' => $domain,
				'route' => $route['url'],
			),
			'url_type'
		);

		if (!$url_types)
		{
			return array();
		}
		$url_types = array_keys($url_types);
		$result = $this->getUrls($urls);

		foreach ($result as $key => $item)
		{
			if (!isset($item['url_type']))
			{
				continue;
			}
			if (!in_array($item['url_type'], $url_types))
			{
				unset($result[$key]);
			}
		}

		return $result;
	}

	private function getUrls($all_data)
	{
		$split_data = $this->getSplitData($all_data);

		$categories = array();
		$products = array();
		$product_pages = array();
		//$product_reviews = array();
		//$pages = array();
		//$seofilters = array();
		//$brands = array();

		foreach ($split_data[shopSeoredirect2Type::CATEGORY] as $category)
		{
			self::setUrlType($category, shopSeoredirect2UrlType::NATURAL);
			$categories = array_merge($categories, array($category), $this->getCategoryUrls($category));
		}

		foreach ($split_data[shopSeoredirect2Type::PRODUCT] as $product)
		{
			$p_natural = array();
			foreach ($split_data[shopSeoredirect2Type::CATEGORY] as $category)
			{
				if ($product['parent_id'] == $category['id'])
				{
					$p_natural[] = $this->getNaturalProductUrl($product, $category);
				}
			}
			$products = array_merge($products, $this->getProductUrls($product), $p_natural);
		}

		foreach ($split_data[shopSeoredirect2Type::PRODUCT_PAGE] as $product_page)
		{
			$p_pages = array();
			foreach ($products as $product)
			{
				if ($product_page['parent_id'] == $product['id'])
				{
					$p_pages[] = $this->getProductPagesUrl($product, $product_page);
				}
			}
			$product_pages = array_merge($product_pages, $p_pages);
		}

		$p_reviews = array();
		foreach ($products as $product)
		{
			$p_reviews[] = $this->getProductReviewsUrl($product);

		}
		$product_reviews = array_merge($product_pages, $p_reviews);

		$pages = $this->filterPage($split_data[shopSeoredirect2Type::PAGE]);

		$seofilters = $this->getSeofilterUrls($categories, $split_data[shopSeoredirect2Type::SEOFILTER]);

		$brands = $this->getBrandsUrls($split_data[shopSeoredirect2Type::PRODUCTBRANDS], $split_data[shopSeoredirect2Type::CATEGORY]);

		$result = array_merge(
			$categories,
			$products,
			$product_pages,
			$product_reviews,
			$pages,
			$seofilters,
			$brands
		);

		return $result;
	}

	private function getSplitData($all_data)
	{
		$categories = array();
		$products = array();
		$product_pages = array();
		$pages = array();
		$seofilters = array();
		$brands = array();

		foreach ($all_data as $data)
		{
			switch ((int)$data['type'])
			{
				case shopSeoredirect2Type::CATEGORY:
					$categories[] = $data;
					break;
				case shopSeoredirect2Type::PRODUCT:
					$products[] = $data;
					break;
				case shopSeoredirect2Type::PRODUCT_PAGE:
					$product_pages[] = $data;
					break;
				case shopSeoredirect2Type::PAGE:
					$pages[] = $data;
					break;
				case shopSeoredirect2Type::SEOFILTER:
					$seofilters[] = $data;
					break;
				case shopSeoredirect2Type::PRODUCTBRANDS:
					$brands[] = $data;
					break;
			}
		}

		return array(
			shopSeoredirect2Type::CATEGORY => $categories,
			shopSeoredirect2Type::PRODUCT => $products,
			shopSeoredirect2Type::PRODUCT_PAGE => $product_pages,
			shopSeoredirect2Type::PAGE => $pages,
			shopSeoredirect2Type::SEOFILTER => $seofilters,
			shopSeoredirect2Type::PRODUCTBRANDS => $brands,
		);
	}

	/**
	 * Возвращает страницы текущей витрины.
	 *
	 * @param $pages
	 * @return mixed
	 */
	private function filterPage($pages)
	{
		$routing = new shopSeoredirect2WaRouting();
		$domain = $routing->getRouting()->getDomain();
		$route = $routing->getRouting()->getRoute();
		$route_url = $route['url'];
		$storefront_pages_model = new shopPageModel();
		$storefront_pages = $storefront_pages_model->getByField(array(
			'domain' => $domain,
			'route' => $route_url
		), true);
		$result = array();

		foreach ($pages as $key => $page)
		{
			foreach ($storefront_pages as $storefront_page)
			{
				if ($page['id'] == $storefront_page['id'])
				{
					$result[] = $page;
				}
			}
		}

		return $result;
	}

	private function getCategoryUrls($data)
	{
		// TODO: routing
		$mixed = $this->getMixedCategoryUrl($data, 'category');
		//$natural = $this->getNaturalCategoryUrl();
		$flat = $this->getFlatUrl($data, 'category');

		return array(
			$mixed,
			$flat,
		);
	}

	private function getProductUrls($data)
	{
		// TODO: routing
		$mixed = $this->getMixedProductUrl($data);
		$flat = $this->getFlatUrl($data, 'product');

		return array(
			$mixed,
			$flat,
		);
	}

	private function getMixedCategoryUrl($data, $url = 'category')
	{
		self::setUrlType($data, shopSeoredirect2UrlType::MIXED);
		$data['full_url'] = $url . '/' . $data['full_url'];

		return $data;
	}

	private function getMixedProductUrl($data)
	{
		self::setUrlType($data, shopSeoredirect2UrlType::MIXED);
		$data['full_url'] = $data['url'];

		return $data;
	}

	/**
	 * @param array $data Строка текущей таблицы
	 * @param string $url 'category' or 'product'
	 * @return mixed
	 */
	private function getFlatUrl($data, $url = 'category')
	{
		self::setUrlType($data, shopSeoredirect2UrlType::FLAT);
		$data['full_url'] = $url . '/' . $data['url'];

		return $data;
	}

	// Not need
	//private function getNaturalCategoryUrl()
	//{
	//}

	private function getNaturalProductUrl($product_data, $category_data = null)
	{
		if ($category_data == null)
		{
			$product_data = $this->getMixedProductUrl($product_data);
			self::setUrlType($product_data, shopSeoredirect2UrlType::NATURAL);
		}
		else
		{
			self::setUrlType($product_data, shopSeoredirect2UrlType::NATURAL);
			$product_data['full_url'] = $category_data['full_url'] . '/' . $product_data['url'];
		}

		return $product_data;
	}

	private function getProductPagesUrl($product_data, $pages_data)
	{
		$pages_data['full_url'] = $product_data['full_url'] . '/' . $pages_data['url'];
		$pages_data['url_type'] = $product_data['url_type'];

		return $pages_data;
	}

	private function getProductReviewsUrl($product_data, $reviews = 'reviews')
	{
		$product_data['full_url'] = $product_data['full_url'] . '/' . $reviews;
		$product_data['type'] = shopSeoredirect2Type::PRODUCT_REVIEWS;

		return $product_data;
	}

	private function getSeofilterUrls($categories, $filters, $all_categories = false)
	{
		if (!shopSeoredirect2Helper::seofilterOver2())
		{
			return array();
		}

		$seofilters = array();
		foreach ($categories as $category)
		{
			foreach ($filters as $filter)
			{
				if ($filter['parent_id'] != $category['id'] && !$all_categories)
				{
					continue;
				}
				$seofilters = array_merge($seofilters, $this->getSeofilterUrl($category, $filter));
			}
		}

		return $seofilters;
	}

	private function getSeofilterUrl($category, $filter)
	{
		$this->setUrlType($filter, $category['url_type']);

		$filter['parent_id'] = $category['id'];
		$filter1 = $filter;
		$filter1['full_url'] = $category['full_url'] . '/' . $filter['url'];
		$filter2 = $filter;
		$filter2['full_url'] = $category['full_url'] . '/filter/' . $filter['url'];
		$filter3 = $filter;
		$filter3['full_url'] = $category['full_url'] . '-' . $filter['url'];

		return array($filter1, $filter2, $filter3);
	}

	private function getBrandsUrls($brands, $categoies)
	{
		if (!shopSeoredirect2Brands::existsBrands())
		{
			return array();
		}

		$result = array();

		foreach ($brands as $key => $brand)
		{
			$brand_url = 'brand/' . $brand['url'];
			$brand['full_url'] = $brand_url;
			$result[] = $brand;
			$brand_id =  $brand['id'];
			foreach ($categoies as $category)
			{
				$brand['parent_id'] = $brand_id;
				$brand['type'] = shopSeoredirect2Type::PRODUCTBRANDS_CATEGORY;
				$brand['id'] = $category['id'];
				$brand['url'] = $brand['url'] . '/' . $category['url'];
				$brand['full_url'] = $brand_url . '/' . $category['url'];
				$result[] = $brand;
				$brand['full_url'] = $brand_url . '/' . $category['full_url'];
				$result[] = $brand;
			}
		}

		return $result;
	}

	private function setUrlType(&$data, $url_type)
	{
		$data['url_type'] = $url_type;
	}

	private function addSeofilterCategoryData($category)
	{
		$seofilter_urls = $this
			->select('*')
			->where('type = :type', array('type' => shopSeoredirect2Type::SEOFILTER))
			->where('parent_id = :category_id', array('category_id' => $category['id']))
			->where('url != full_url')
			->query();

		foreach ($seofilter_urls as $seofilter_url)
		{
			if (substr($seofilter_url['full_url'], 7) !== 'filter/')
			{
				continue;
			}

			$filter_url = substr($seofilter_url['full_url'], -strlen($seofilter_url['url']));
			$new_full_url = $category['url'] . '-' . $filter_url;

			if ($new_full_url === $seofilter_url['full_url'])
			{
				continue;
			}

			$url_to_update = array(
				'id' => $seofilter_url['id'],
				'type' => shopSeoredirect2Type::SEOFILTER,
				'url' => $seofilter_url['url'],
				'full_url' => $new_full_url,
				'parent_id' => intval($category['id']),
				'create_datetime' => date('Y-m-d H:i:s'),
			);

			$this->addData($url_to_update);
		}
	}

	/**
	 * @param string|array $hash
	 */
	public function delete($hash)
	{
		if (!$hash)
		{
			return;
		}
		$this->deleteByField('hash', $hash);
	}

	public function recalculateHash()
	{
		foreach ($this->select('*')->query() as $row)
		{
			$new_hash = $this->getHash($row);

			if ($new_hash != $row['hash'])
			{
				$this->delete($row['hash']);
				$this->insert($row, waModel::INSERT_IGNORE);
			}
		}
	}

	/**
	 * @param string[] $url_parts
	 * @return bool
	 */
	private function canBeCatalogreviews($url_parts)
	{
		$catalogreviews_helper = shopSeoredirect2CatalogreviewsPluginHelper::getInstance();
		$plugin_env = $catalogreviews_helper->getPluginEnv();
		if (count($url_parts) < 2 || !$plugin_env)
		{
			return false;
		}

		$config = $plugin_env->getConfig();
		if ($config->url_type === shopCatalogreviewsPluginUrlType::KEYWORD_PREFIX)
		{
			return $url_parts[0] === $config->root_url_keyword;
		}
		elseif ($config->url_type === shopCatalogreviewsPluginUrlType::KEYWORD_POSTFIX)
		{
			return $url_parts[count($url_parts) - 1] === $config->root_url_keyword;
		}
		else
		{
			return false;
		}
	}

    public function cleanUrls()
    {
        $this->deleteByField([
           'url' => '',
            'id' => 0,
        ])->limit(5000);
    }
}
