<?php

class shopSeoredirect2CatalogreviewsPluginHelper
{
	private static $instance;

	private $category_model;
	private $plugin_instance;
	private $seofilter_filter_storage;
	private $seofilter_plugin_helper;

	protected function __construct()
	{
		$this->category_model = new shopCategoryModel();
		$this->plugin_instance = $this->createPluginInstance();
		$this->seofilter_plugin_helper = shopSeoredirect2SeofilterPluginHelper::getInstance();
		$this->seofilter_filter_storage = $this->seofilter_plugin_helper->getFilterStorage();
	}

	/**
	 * @return shopSeoredirect2CatalogreviewsPluginHelper
	 */
	public static function getInstance()
	{
		if (!isset(self::$instance))
		{
			self::$instance = new self();
		}

		return self::$instance;
	}

	public function isPluginInstalled()
	{
		$plugin_info = wa('shop')->getConfig()->getPluginInfo('catalogreviews');

		return is_array($plugin_info) && $plugin_info !== array();
	}

	public function isPluginEnabled()
	{
		$plugin_env = $this->getPluginEnv();

		return $plugin_env && $plugin_env->getConfig()->plugin_is_enabled;
	}

	public function isCategoryPageEnabled($storefront, $category_id)
	{
		$context = shopCatalogreviewsContext::getFrontendInstance();

		$category = $this->category_model->getById($category_id);
		if (!$category['status'])
		{
			return false;
		}

		$full_config = $context->getConfigStorage()->getFullConfig($storefront, $category_id, null);

		return $full_config->plugin_is_enabled && $full_config->category_is_enabled;
	}

	public function isSeofilterPageEnabled($storefront, $category_id, $seofilter_filter_id)
	{
		$context = shopCatalogreviewsContext::getFrontendInstance();

		$category = $this->category_model->getById($category_id);
		if (!$category['status'] || !$this->seofilter_plugin_helper->isPluginEnabled())
		{
			return false;
		}

		$seofilter_filter = $this->seofilter_filter_storage->getById($seofilter_filter_id);
		if (!$seofilter_filter)
		{
			return false;
		}

		$full_config = $context->getConfigStorage()->getFullConfig($storefront, $category_id, $seofilter_filter);

		return $full_config->plugin_is_enabled && $full_config->category_is_enabled && $full_config->seofilter_is_enabled;
	}

	/**
	 * @return shopCatalogreviewsPluginEnv|null
	 */
	public function getPluginEnv()
	{
		return $this->plugin_instance
			? $this->plugin_instance->getEnv()
			: null;
	}

	/**
	 * @return shopCatalogreviewsPlugin|null
	 */
	private function createPluginInstance()
	{
		$plugin = null;
		if ($this->isPluginInstalled())
		{
			try
			{
				$plugin = wa('shop')->getPlugin('catalogreviews');
			}
			catch (Exception $e)
			{
			}
		}

		return $plugin;
	}
}