<?php


class shopSeoredirect2Plugin extends shopPlugin
{
	protected static $is_routed = false;
	protected static $is_headed = false;

	/**
	 * @return shopSeoredirect2Plugin
	 * @throws waException
	 */
	public static function getInstance()
	{
		return wa('shop')->getPlugin('seoredirect2');
	}

	public static function isCli()
	{
		return PHP_SAPI == 'cli';
	}

	public static function isBackend()
	{
		return wa()->getEnv() == 'backend';
	}

	public function routing($route = array())
	{
		if (self::isCli())
		{
			return;
		}

		if (self::isBackend())
		{
			return;
		}

		if (!self::$is_routed)
		{
			self::$is_routed = true;
			$this->frontendHead();
		}
	}

	/**
	 * Хук. Срабатывает при каждой загрузке страницы магазина.
	 * Используется для проверки наличия и, если он есть, осуществления редиректа.
	 *
	 * @return string
	 */
	public function frontendHead()
	{
		if (!self::$is_headed)
		{
			self::$is_headed = true;
		}
		else
		{
			return '';
		}
		$is_enable = $this->getSettings('enable');

		if (!$is_enable)
		{
			return '';
		}

		$this->addBrand();

		$domain = wa()->getRouting()->getDomain();

		$url_type = waRequest::param('url_type');
		if ($url_type !== null)
		{
			$route = wa()->getRouting()->getRoute();
			$seoredirect2_domain_model = new shopSeoredirect2ShopUrlTypeModel();
			$seoredirect2_domain_model->addData($domain, $route['url'], $url_type);
		}

		$this->redirect();

		return '';
	}

	/**
	 * Для debug режима. Если товар удален показывает редирект в зависимости от настроек
	 *
	 * @param $redirect
	 * @param $type
	 * @param shopSeoredirect2Settings $settings
	 * @return mixed|null|string
	 */
	public function checkRedirect($redirect, $type, shopSeoredirect2Settings $settings)
	{
		if ($redirect)
		{
			return $redirect;
		}
		if ($type == shopSeoredirect2Type::PRODUCT || $type == shopSeoredirect2Type::PRODUCT_PAGE)
		{
			if ($settings->codeProductDelete())
			{
				if ($settings->productDeleteOn() == 'category')
				{
					return 'на категорию'; // TODO: может быть надо сделать
				}
				elseif ($settings->productDeleteOn() == 'url')
				{
					return $settings->productDeleteOnUrl();
				}
			}
		}
		if ($type == shopSeoredirect2Type::SEOFILTER)
		{
			if ($settings->codeSeofilterDelete())
			{
				if ($settings->seofilterDeleteOn() == 'category')
				{
					return 'на категорию';
				}
				elseif ($settings->seofilterDeleteOn() == 'url')
				{
					return $settings->seofilterDeleteOnUrl();
				}
			}
		}

		return null;
	}

	/**
	 * Хук. Срабатывает при ошибке.
	 * Используется для записи данных об ошибке
	 *
	 * @param waException $params
	 * @return null
	 */
	public function frontendError(waException $params)
	{
		$settings_array = $this->getSettings();
		$settings = new shopSeoredirect2Settings($settings_array);

		if (!$settings->isEnable() || !$params || $params->getCode() !== 404)
		{
			return null;
		}

		$filter_url = waRequest::param('seofilter_filter_url', '');
		$filter_url .= empty($filter_url) ? '' : '/';
		$redirect_gen = new shopSeoredirect2RedirectGenerator(wa()->getRouting()->getCurrentUrl() . $filter_url);

		if ($redirect_gen->findUrl()->isFound())
		{
			$redirect_gen->generateRedirect();

			$url_type = waRequest::param('url_type', null, 'int');

			$type = $redirect_gen->getType();
			$id = $redirect_gen->getId();
			$parent_id = $redirect_gen->getParentId();
			$code = null;

			if ($settings->isDebug() && wa()->getUser()->isAdmin())
			{
				$this->debug(array(
					'id' => $id,
					'type' => $type,
					'parent_id' => $parent_id,
					'autoredirect' => array(
						'id' => $id,
						'type' => $type,
						'parent_id' => $parent_id,
					),
					'current_url' => shopSeoredirect2Url::getFullUrl() . $filter_url,
					'redirect' => $this->checkRedirect($redirect_gen->getRedirect(), $type, $settings),
				));
				return;
			}

			switch ($type) {
				case shopSeoredirect2Type::CATEGORY:
					if ($settings->codeCategoryChange())
					{
						$this_url_type = $url_type === $redirect_gen->getUrlType();
						if (!$settings->codeUrlTypeChange())
						{
							if (!$this_url_type)
							{
								return null;
							}
						}

						if ($redirect_gen->getRedirect())
						{
							$code = $this_url_type ? $settings->codeCategoryChange() : $settings->codeUrlTypeChange();
							$redirect_gen->toRedirect($code);
						}
						else
						{
							if ($settings->codeCategoryDelete())
							{
								$code = $this_url_type ? $settings->codeCategoryDelete() : $settings->codeUrlTypeChange();
								if ($settings->categoryDeleteOn() == 'home')
								{
									$frontend_url = wa('shop')->getRouteUrl('shop', array(
										'module' => 'frontend',
									), true);
									if ($frontend_url)
									{
										shopSeoredirect2WaResponse::redirect($frontend_url, $code);
									}
								}
								elseif ($settings->categoryDeleteOn() == 'url')
								{
									$frontend_url = $settings->categoryDeleteOnUrl();
									shopSeoredirect2WaResponse::redirect($frontend_url, $code);
								}
							}
						}
					}
					break;
				case shopSeoredirect2Type::PRODUCT:
					if ($settings->codeProductChange())
					{
						$this_url_type = $url_type === $redirect_gen->getUrlType();
						if (!$settings->codeUrlTypeChange())
						{
							if (!$this_url_type)
							{
								return null;
							}
						}

						if ($redirect_gen->getRedirect())
						{
							$code = $this_url_type ? $settings->codeProductChange() : $settings->codeUrlTypeChange();
							$redirect_gen->toRedirect($code);
						}
						else
						{
							if ($settings->codeProductDelete())
							{
								$code = $this_url_type ? $settings->codeProductDelete() : $settings->codeUrlTypeChange();
								if ($settings->productDeleteOn() == 'category')
								{
									$url_generator = new shopSeoredirect2ShopUrlGenerator();
									$frontend_url = $url_generator->getUrl(
										new shopSeoredirect2Autoredirect(array(
											'id' => $parent_id,
											'type' => shopSeoredirect2Type::CATEGORY
										))
									);
									if ($frontend_url)
									{
										shopSeoredirect2WaResponse::redirect($frontend_url, $code);
									}
								}
								elseif ($settings->productDeleteOn() == 'url')
								{
									$frontend_url = $settings->productDeleteOnUrl();
									shopSeoredirect2WaResponse::redirect($frontend_url, $code);
								}
							}
						}
					}
					break;
				case shopSeoredirect2Type::PRODUCT_PAGE:
					if ($settings->codeProductPageChange())
					{
						$this_url_type = $url_type === $redirect_gen->getUrlType();
						if (!$settings->codeUrlTypeChange())
						{
							if (!$this_url_type)
							{
								return null;
							}
						}
						if ($redirect_gen->getRedirect())
						{
							$code = $this_url_type ? $settings->codeProductPageChange() : $settings->codeUrlTypeChange();
							$redirect_gen->toRedirect($code);
						}
						else
						{
							if ($settings->codeProductPageDelete())
							{
								$code = $settings->codeProductPageDelete();
								$url_generator = new shopSeoredirect2ShopUrlGenerator();
								$frontend_url = $url_generator->getUrl(
									new shopSeoredirect2Autoredirect(array(
										'id' => $parent_id,
										'type' => shopSeoredirect2Type::PRODUCT
									))
								);
								if ($frontend_url)
								{
									shopSeoredirect2WaResponse::redirect($frontend_url, $code);
								}
							}

						}
					}
					break;
				case shopSeoredirect2Type::PAGE:
					if ($settings->codePageChange())
					{
						$code = $settings->codePageChange();
						$redirect_gen->toRedirect($code);
					}
					break;
				case shopSeoredirect2Type::SEOFILTER:
					if (!shopSeoredirect2Helper::seofilterOver2())
					{
						return null;
					}
					if ($settings->codeSeofilterChange())
					{
						if ($redirect_gen->getRedirect())
						{
							$code = $settings->codeSeofilterChange();
							$redirect_gen->toRedirect($code);
						}
						else
						{
							if ($settings->codeSeofilterDelete())
							{
								$code = $settings->codeSeofilterDelete();
								if ($settings->seofilterDeleteOn() == 'category')
								{
									$url_generator = new shopSeoredirect2ShopUrlGenerator();
									$frontend_url = $url_generator->getUrl(
										new shopSeoredirect2Autoredirect(array(
											'id' => $parent_id,
											'type' => shopSeoredirect2Type::CATEGORY
										))
									);
									if ($frontend_url)
									{
										shopSeoredirect2WaResponse::redirect($frontend_url, $code);
									}
								}
								elseif ($settings->seofilterDeleteOn() == 'url')
								{
									$frontend_url = $settings->seofilterDeleteOnUrl();
									shopSeoredirect2WaResponse::redirect($frontend_url, $code);
								}
							}
						}

					}
					break;
				case shopSeoredirect2Type::PRODUCT_REVIEWS:
					// TODO: сократить
					if ($settings->codeProductChange())
					{
						$this_url_type = $url_type === $redirect_gen->getUrlType();
						if (!$settings->codeUrlTypeChange())
						{
							if (!$this_url_type)
							{
								return null;
							}
						}

						if ($redirect_gen->getRedirect())
						{
							$code = $this_url_type ? $settings->codeProductChange() : $settings->codeUrlTypeChange();
							$redirect_gen->toRedirect($code);
						}
						else
						{
							if ($settings->codeProductDelete())
							{
								$code = $this_url_type ? $settings->codeProductDelete() : $settings->codeUrlTypeChange();
								if ($settings->productDeleteOn() == 'category')
								{
									$url_generator = new shopSeoredirect2ShopUrlGenerator();
									$frontend_url = $url_generator->getUrl(
										new shopSeoredirect2Autoredirect(array(
											'id' => $parent_id,
											'type' => shopSeoredirect2Type::CATEGORY
										))
									);
									if ($frontend_url)
									{
										shopSeoredirect2WaResponse::redirect($frontend_url, $code);
									}
								}
								elseif ($settings->productDeleteOn() == 'url')
								{
									$frontend_url = $settings->productDeleteOnUrl();
									shopSeoredirect2WaResponse::redirect($frontend_url, $code);
								}
							}
						}
					}
					break;
				case shopSeoredirect2Type::PRODUCTBRANDS:
					if ($settings->codeProductBrandsChange())
					{
						$redirect_gen->toRedirect($settings->codeProductBrandsChange());
					}
					break;
				case shopSeoredirect2Type::PRODUCTBRANDS_CATEGORY:
					if ($settings->codeProductBrandsChange())
					{
						$redirect_gen->toRedirect($settings->codeProductBrandsChange());
					}
					break;
			}
		}

		shopSeoredirect2ViewHelper::createErrorNode($params->getCode());

		return null;
	}

	/**
	 * Хук. Срабатывает при открытии страниц товара.
	 * Используется для записи данных.
	 *
	 * @param shopProduct $product
	 * @return array
	 */
	public function frontendProduct(shopProduct $product)
	{
		$shop_urls_model = new shopSeoredirect2ShopUrlsModel();
		$shop_urls_model->addProductData($product);

		return array();
	}

	/**
	 * Хук. Срабатывает при открытии страниц категорий.
	 * Используется для записи данных.
	 *
	 * @param $params
	 * @return string
	 */
	public function frontendCategory($params)
	{
		$shop_urls_model = new shopSeoredirect2ShopUrlsModel();
		$shop_urls_model->addCategoryData($params);
		return '';
	}

	/**
	 * Хук. Срабатывает при удалении категории.
	 * Используется для записи данных об изменении адреса.
	 *
	 * @param $category
	 */
	public function categoryDelete($category)
	{
		$shop_urls_model = new shopSeoredirect2ShopUrlsModel();
		$shop_urls_model->addCategoryData($category);
	}

	/**
	 * Хук. Срабатывает при открытии окна редактирования категории.
	 * Используется для записи данных об изменении адреса. (???)
	 *
	 * @param $category
	 */
	public function backendCategoryDialog($category)
	{
		$shop_urls_model = new shopSeoredirect2ShopUrlsModel();
		$shop_urls_model->addCategoryData($category);
	}

	/**
	 * Хук. Срабатывает при сохранении категории.
	 * Используется для записи данных об изменении адреса. (???)
	 *
	 * @param $params
	 */
	public function categorySave($params)
	{
		$category_model = new shopCategoryModel();
		$category_id = $params['id'];
		$category = $category_model->getById($category_id);

		if ($category)
		{
			$shop_urls_model = new shopSeoredirect2ShopUrlsModel();
			$shop_urls_model->addCategoryData($category);
		}
	}

	/**
	 * Хук. Срабатывает при открытии страницы редактирования товара.
	 * Используется для записи данных об изменении адреса.
	 *
	 * @param shopProduct $product
	 * @return array
	 */
	public function backendProductEdit(shopProduct $product)
	{
		if (!$product)
		{
			return array();
		}

		if ($product->id != 'new')
		{
			$shop_urls_model = new shopSeoredirect2ShopUrlsModel();
			$shop_urls_model->addProductData($product);
		}

		return array();
	}

	/**
	 * Хук. Срабатывает при удалении товара.
	 * Используется для записи данных об изменении адреса.
	 *
	 * @param $params
	 */
	public function productDelete($params)
	{
		$shop_urls_model = new shopSeoredirect2ShopUrlsModel();

		foreach ($params['ids'] as $id)
		{
			$product = new shopProduct($id);
			$shop_urls_model->addProductData($product);
		}
	}

	/**
	 * Хук. Срабатывает при сохранении товара.
	 * Используется для записи данных об изменении адреса.
	 *
	 * @param $params
	 */
	public function productSave($params)
	{
		$shop_urls_model = new shopSeoredirect2ShopUrlsModel();
		$product = new shopProduct($params['data']['id']);
		$shop_urls_model->addProductData($product);
	}

	/**
	 * Хук. Срабатывает при редактировании страницы.
	 * Изпользуется для записи данных об измении адреса.
	 *
	 * @param $params
	 */
	public function pageEdit($params)
	{
		$shop_urls_model = new shopSeoredirect2ShopUrlsModel();
		$page = $params['page'];
		if (isset($page['id']))
		{
			$shop_urls_model->addPageData($page);
		}
	}

	/**
	 * Хук. Срабатывает при открытии страницы редактирования страницы.
	 * Изпользуется для записи данных об измении адреса.
	 *
	 * @param $params
	 */
	public function backendPageEdit($params)
	{
		$shop_urls_model = new shopSeoredirect2ShopUrlsModel();
		$page = $params;
		if (isset($page['id']))
		{
			$shop_urls_model->addPageData($page);
		}
	}

	/**
	 * Хук. Срабатывает при сохранении страницы.
	 * Изпользуется для записи данных об измении адреса.
	 *
	 * @param $params
	 */
	public function pageSave($params)
	{
		$shop_urls_model = new shopSeoredirect2ShopUrlsModel();
		$old_page = $params['old'];
		$shop_urls_model->addPageData($old_page);
		$page = $params['page'];
		$shop_urls_model->addPageData($page);
	}

	/**
	 * Хук. Срабатывает при сохранении фильтра.
	 *
	 * @param shopSeofilterFilterAttributes $filter
	 */
	public function shopSeofilterFilterSave(shopSeofilterFilterAttributes $filter)
	{
		$this->shopSeofilterFrontend(array(
			'filter' => $filter,
			'category_id' => 0
		));
	}

	/**
	 * Хук. Срабатывает на страницах созданных плагином seofilter.
	 *
	 * @param $params
	 * @throws waException
	 */
	public function shopSeofilterFrontend($params)
	{
		if (!class_exists("shopSeofilterFilterAttributes"))
		{
			return;
		}

		if (!$params['filter'] instanceof shopSeofilterFilterAttributes)
		{
			throw new waException('$params[\'filter\'] is not an instanceof shopSeofilterFilterAttributes');
		}

		$shop_urls_model = new shopSeoredirect2ShopUrlsModel();
		$shop_urls_model->addSeofilterData(array(
			'id' => $params['filter']->getId(),
			'url' => $params['filter']->getUrl(),
			'full_url' => $params['filter']->getFullUrl(),
			'category_id' => $params['category_id']
		));
	}

	public function handleFrontendCatalogreviews($params)
	{
		/** @var shopCatalogreviewsPluginEnv $plugin_env */
		$plugin_env = ifset($params, 'plugin_env', null);
		if (!$plugin_env)
		{
			return null;
		}

		$shop_urls_model = new shopSeoredirect2ShopUrlsModel();
		$shop_urls_model->addCatalogreviewsData(
			wa()->getRouting()->getCurrentUrl(),
			$plugin_env->getCategory(),
			$plugin_env->getSeofilterFilter()
		);

		return null;
	}

    public function handleBackendMenu()
    {
        $plugin = shopSeoredirect2Plugin::getInstance();
        $settings = $plugin->getSettings();
        if(!$settings['errors']) {
            return;
        }
//        $start_clean =  strtotime($settings['start_clean']) + 30*24*60*60;
//        if(time()>$start_clean) {
//           if(isset($settings['last_clean']) && $settings['last_clean'] === date('Y-m-d')){
//               return [];
//           }

        $clean_errors = ifset($settings['clean_errors'], 30);
        $clean_errors = $clean_errors === '' ? 30 : $clean_errors;
        $clean_errors_data = ifset($settings['clean_errors_data'], 7);
        $clean_errors_data = $clean_errors_data === '' ? 7 : $clean_errors_data;

           if($clean_errors === 'never'){
               return [];
           }

           $errors_storage = new shopSeoredirect2ErrorStorage();
           $errors_storage->cleanErrorsByTime($clean_errors, $clean_errors_data);

           $settings['last_clean'] =  date('Y-m-d');
           $plugin->saveSettings($settings);
//        }

        return [];

    }

	/**
	 * Добавляет brand в архив урлов
	 */
	public function addBrand()
	{
		if (!shopSeoredirect2Brands::existsBrands())
		{
			return;
		}
		$brand_url = new shopSeoredirect2BrandsUrl();
		if (!$brand_url->isBrandsPage())
		{
			return;
		}
		if (!$brand_url->statusOk())
		{
			return;
		}
		$brand = shopSeoredirect2Brands::getBrandByUrl($brand_url->getBrandUrl());
		if (empty($brand))
		{
			return;
		}
		$model = new shopSeoredirect2ShopUrlsModel();

		$model->addBrandData($brand);
	}

	/**
	 * Возвращает настройки с учётом настроек по-умолчанию
	 *
	 * @param null $name
	 * @return array|mixed|null|string
	 */
	public function getSettings($name = null)
	{
		$settings = parent::getSettings();

		return shopSeoredirect2Settings::getSettings($settings, $name);
	}

	private function redirect()
	{
		$settings_array = $this->getSettings();
		$settings = new shopSeoredirect2Settings($settings_array);

		if (!($settings->isRedirectXMLHttp()) && waRequest::isXMLHttpRequest())
		{
			return;
		}

		$domain = wa()->getRouting()->getDomain();
        if(function_exists('idn_to_utf8')) {
            $domain = idn_to_utf8($domain, 0, INTL_IDNA_VARIANT_UTS46);
        }
		$url = new shopSeoredirect2Url();
		$redirect_model = new shopSeoredirect2RedirectModel();
		// static
		$current_url = $url->getPath() . $url->getQuery();
		
		$current_url = urldecode($current_url);


		$redirect = $redirect_model->getByDomainAndUrl($domain, $current_url);

		if (!$redirect)
		{
			$redirect = $redirect_model->getByDomainAndUrl($domain, $url->getPath());
		}

        $data = [
            'visit_datetime' => date('Y-m-d H:i:s')
        ];

		if ($redirect)
		{
			$redirect_obj = new shopSeoredirect2Redirect($redirect);
			if ($redirect_obj->isActive())
			{
				$redirect_obj->setUrl($url);
				$redirect_model->updateById($redirect_obj->getId(), $data);
				$redirect_obj->redirect();
			}
		}
		// regular
		$redirects = $redirect_model
			->select('*')
			->order("sort ASC")
			->where('type='.shopSeoredirect2Redirect::TYPE_REGULAR)
			->fetchAll();
		foreach ($redirects as $redirect)
		{
			$obj = new shopSeoredirect2Redirect($redirect);
			if (($obj->equalDomain($domain) || $obj->isGeneral()) && $obj->isActive())
			{
				$obj->setUrl($url);
				if (!$obj->equalUrlFrom())
				{
					continue;
				}
                $redirect_model->updateById($obj->getId(), $data);
				$obj->redirect();
			}
		}
	}

	private function debug($info)
	{
		wa()->getView()->assign($info);

		echo wa()->getView()->fetch($this->path . '/templates/DebugInfo.html');

		exit;
	}
}
