<?php

class shopSeoredirect2Settings
{
	protected $settings = array();

	public function __construct($settings = array())
	{
		$this->settings = self::getSettings($settings);
	}

	public function __set($name, $value)
	{
		$this->settings[$name] = $value;
	}

	public function __get($name)
	{
		if (array_key_exists($name, $this->settings)) {
			return $this->settings[$name];
		}

		return null;
	}

	public function __isset($name)
	{
		return isset($this->settings[$name]);
	}

	public function __unset($name)
	{
		unset($this->settings[$name]);
	}

	/**
	 * @param string $name
	 * @return mixed
	 */
	public function getSetting($name)
	{
		return isset($this->settings[$name]) ? $this->settings[$name] : null;
	}

	public function setSetting($name, $value)
	{
		$this->settings[$name] = $value;
	}

	/**
	 * @return bool
	 */
	public function isEnable()
	{
		return (bool)$this->getSetting('enable');
	}

	/**
	 * @return bool
	 */
	public function isCustom()
	{
		return (bool)$this->getSetting('custom');
	}

	public function codeCategoryChange()
	{
		return (int)$this->getSetting('category_change');
	}

	public function codeCategoryDelete()
	{
		return (int)$this->getSetting('category_delete');
	}

	public function categoryDeleteOn()
	{
		return (string)$this->getSetting('category_delete_on');
	}

	public function categoryDeleteOnUrl()
	{
		return (string)$this->getSetting('category_delete_on_url');
	}

	public function codeProductChange()
	{
		return (int)$this->getSetting('product_change');
	}

	public function codeProductPageChange()
	{
		return (int)$this->getSetting('product_page_change');
	}

	public function codePageChange()
	{
		return (int)$this->getSetting('page_change');
	}

	public function codeSeofilterChange()
	{
		return (int)$this->getSetting('seofilter_change');
	}

	public function codeUrlTypeChange()
	{
		return (int)$this->getSetting('url_type_change');
	}

	public function codeProductDelete()
	{
		return (int)$this->getSetting('product_delete');
	}

	public function productDeleteOn()
	{
		return (string)$this->getSetting('product_delete_on');
	}

	public function productDeleteOnUrl()
	{
		return (string)$this->getSetting('product_delete_on_url');
	}

	public function codeProductPageDelete()
	{
		return (int)$this->getSetting('product_page_delete');
	}

	public function codeSeofilterDelete()
	{
		return (int)$this->getSetting('seofilter_delete');
	}

	public function seofilterDeleteOn()
	{
		return (string)$this->getSetting('seofilter_delete_on');
	}

	public function seofilterDeleteOnUrl()
	{
		return (string)$this->getSetting('seofilter_delete_on_url');
	}

	public function codeProductBrandsChange()
	{
		return (string)$this->getSetting('productbrands_change');
	}

	public function isRedirectXMLHttp()
	{
		return (bool)$this->getSetting('is_redirect_xmlhttp');
	}

	public function isErrors()
	{
		return (bool)$this->getSetting('errors');
	}

	public function isDebug()
	{
		return (bool)$this->getSetting('debug');
	}

	/**
	 * Возвращает настройки по-умолчанию
	 *
	 * @return array
	 */
	private static function defaultSettings()
	{
		return array(
			'enable' => 0,
			'custom' => 1,
			'category_change' => 301, // [0, 301, 302]
			'category_delete' => 0,
			'category_delete_on' => 'home',
			'category_delete_on_url' => '',
			'product_change' => 301,
			'product_page_change' => 301,
			'page_change' => 301,
			'seofilter_change' => 301,
			'url_type_change' => 301,
			'product_delete' => 0,
			'product_delete_on' => 'category', // [category, url]
			'product_delete_on_url' => '', // string
			'product_page_delete' => 301,
			'seofilter_delete' => 0,
			'seofilter_delete_on' => 'category', // [category, url]
			'seofilter_delete_on_url' => '', // string
			'productbrands_change' => 301,
			'is_redirect_xmlhttp' => 1,
			'errors' => 1,
			'debug' => 0,
		);
	}

	/**
	 * Возвращает настройки с учётом настроек по-умолчанию
	 *
	 * @param array $settings
	 * @param null $name
	 * @return array|mixed|null|string
	 */
	public static function getSettings($settings, $name = null)
	{
		$default_settings = self::defaultSettings();
		$is_use_default = ifset($settings['custom'], $default_settings['custom']); // custom - это по-умолчанию ;)

		foreach ($default_settings as $key => $value)
		{
			if (!isset($settings[$key]))
			{
				$settings[$key] = $default_settings[$key];
			}
		}

		if ($is_use_default)
		{
			foreach ($default_settings as $key => $value)
			{
				if (!in_array($key, array('enable', 'errors', 'debug')))
				{
					$settings[$key] = $default_settings[$key];
				}
			}
		}

		return !is_null($name) ? ifset($settings[$name]) : $settings;
	}
}
