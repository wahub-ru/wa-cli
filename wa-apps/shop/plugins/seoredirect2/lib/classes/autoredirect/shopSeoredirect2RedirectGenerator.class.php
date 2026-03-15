<?php

class shopSeoredirect2RedirectGenerator
{
	const MULTIPLE_CHOICES = 300;
	const MOVED_PERMANENTLY = 301;
	const MOVED_TEMPORARILY = 302;
	const FOUND = 302;
	const SEE_OTHER = 303;
	const NOT_MODIFIED = 304;

	/**
	 * @var shopSeoredirect2ShopUrl
	*/
	protected $url;

	protected $redirect;

	protected $is_found;

	/**
	 * @var int shopSeoredirect2Type
	 */
	protected $type;

	protected $id;

	protected $parent_id;

	protected $url_type;

	/**
	 * @var shopSeoredirect2Autoredirect[]
	 */
	protected $redirect_items;

	/**
	 * @var shopSeoredirect2Autoredirect|null
	 */
	protected $current_redirect_item = null;

	/**
	 * @var shopSeoredirect2ShopUrlsModel
	*/
	protected $shop_urls_model;

	/**
	 * Example:
	 * <code>
	 * $redirect = new shopSeoredirect2ShopUrl();
	 * $redirect->findUrl()->generateRedirect()->toRedirect();
	 * </code>
	 *
	 * shopSeoredirect2Redirect constructor.
	 * @param null| string $url
	 */
	public function __construct($url = null)
	{
		$this->url = new shopSeoredirect2ShopUrl($url);
		$this->shop_urls_model = new shopSeoredirect2ShopUrlsModel();
		$this->is_found = false;
	}

	public function __toString()
	{
		return (string)$this->getRedirect();
	}

	/**
	 * @return mixed
	 */
	public function getRedirect()
	{
		return $this->redirect;
	}

	/**
	 * Если url существовал, вернет true
	 *
	 * @return boolean
	 */
	public function isFound()
	{
		return (boolean)$this->is_found;
	}

	/**
	 * @return int shopSeoredirect2Type
	 */
	public function getType()
	{
		return $this->type;
	}

	/**
	 * @return mixed
	 */
	public function getId()
	{
		return $this->id;
	}

	/**
	 * @return int
	 */
	public function getParentId()
	{
		return $this->parent_id;
	}

	/**
	 * @return mixed
	 */
	public function getUrlType()
	{
		return $this->url_type;
	}

	public function findUrl()
	{
		$shop_urls = $this->shop_urls_model->getByUrl($this->url->getExplodeCurrentUrl(), $can_be_catalogreviews);
		$url = mb_strtolower((string)$this->url);
		$catalogreviews_url = '';
		if ($can_be_catalogreviews)
		{
			$catalogreviews_url = $this->getCatalogreviewsUrl($url);
		}

		$this->redirect_items = [];

		$routing = new shopSeoredirect2WaRouting();
		$storefront = $routing->getCurrentStorefront();
		$catalogreviews_helper = shopSeoredirect2CatalogreviewsPluginHelper::getInstance();

		foreach ($shop_urls as $shop_url)
		{
			if (
				mb_strtolower($shop_url['full_url']) == $url
				|| ($can_be_catalogreviews && mb_strtolower($shop_url['full_url']) == $catalogreviews_url)
			)
			{
				if ($can_be_catalogreviews)
				{
					if ($shop_url['type'] == shopSeoredirect2Type::CATEGORY)
					{
						$can_be_catalogreviews = $catalogreviews_helper->isCategoryPageEnabled($storefront, $shop_url['id']);
					}
					elseif ($shop_url['type'] == shopSeoredirect2Type::SEOFILTER)
					{
						$can_be_catalogreviews = $catalogreviews_helper->isSeofilterPageEnabled($storefront, $shop_url['parent_id'], $shop_url['id']);
					}
					else
					{
						$can_be_catalogreviews = false;
					}
				}

				$this->id = $shop_url['id'];
				$this->type = (int)$shop_url['type'];
				$this->parent_id = (int)$shop_url['parent_id'];
				$this->url_type = ifset($shop_url['url_type']);
				$this->redirect_items[] = new shopSeoredirect2Autoredirect(array(
					'id' => $this->id,
					'type' => $this->type,
					'parent_id' => $this->parent_id,
					'url' => $shop_url['url'],
					'full_url' => $shop_url['full_url'],
					'url_type' => ifset($shop_url['url_type']),
				), $can_be_catalogreviews);
				$this->is_found = true;
			}
		}

		return $this;
	}

	public function generateRedirect()
	{
		if ($this->isFound() && empty($this->redirect))
		{
			$redirect = null;

			$url_generator = new shopSeoredirect2ShopUrlGenerator();
			foreach ($this->redirect_items as $redirect_item)
			{
				$redirect = $url_generator->getUrl($redirect_item);

				if ($redirect)
				{
					$this->current_redirect_item = $redirect_item;

					$this->id = $redirect_item->getId();
					$this->type = $redirect_item->getType();
					$this->parent_id = $redirect_item->getParentId();
					$this->url_type = $redirect_item->getUrlType();

					break;
				}
			}

			if ($redirect)
			{
				$redirect .= $this->url->getQuery();
			}

			try
			{
				$current_url = shopSeoredirect2Url2::getCurrentUrl();
				$redirect_url = shopSeoredirect2Url2::parse($redirect);

				if ((string)$current_url != (string)$redirect_url)
				{
					$this->redirect = (string)$redirect_url;
				}
			}
			catch (waException $e)
			{
			}
		}

		return $this;
	}

	/**
	 * @param $code
	 */
	public function toRedirect($code = self::MOVED_TEMPORARILY)
	{
		if ($this->redirect)
		{
			shopSeoredirect2WaResponse::redirect($this->redirect, $code);
		}
	}

	/**
	 * @return shopSeoredirect2Autoredirect
	 */
	public function getRedirectItem()
	{
		return $this->current_redirect_item;
	}

	private function getCatalogreviewsUrl($url)
	{
		$catalogreviews_helper = shopSeoredirect2CatalogreviewsPluginHelper::getInstance();

		$url_parts = explode('/', $url);
		$plugin_env = $catalogreviews_helper->getPluginEnv();
		if (count($url_parts) < 2 || !$plugin_env)
		{
			return null;
		}

		$config = $plugin_env->getConfig();
		if (
			$config->url_type === shopCatalogreviewsPluginUrlType::KEYWORD_PREFIX
			&& $url_parts[0] === $config->root_url_keyword
		)
		{
			return implode('/', array_slice($url_parts, 1));
		}
		elseif (
			$config->url_type === shopCatalogreviewsPluginUrlType::KEYWORD_POSTFIX
			&& $url_parts[count($url_parts) - 1] === $config->root_url_keyword
		)
		{
			return implode('/', array_slice($url_parts, 0, -1));
		}
		else
		{
			return null;
		}
	}
}
