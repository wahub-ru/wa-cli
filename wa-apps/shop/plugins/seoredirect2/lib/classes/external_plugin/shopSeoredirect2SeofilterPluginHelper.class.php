<?php

class shopSeoredirect2SeofilterPluginHelper
{
	private static $instance;

	private $filter_storage;

	private function __construct()
	{
		$this->filter_storage = $this->createFilterStorageInstance();
	}

	/**
	 * @return shopSeoredirect2SeofilterPluginHelper
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
		$plugin_info = wa('shop')->getConfig()->getPluginInfo('seofilter');
		if ($plugin_info === array())
		{
			return false;
		}

		if (version_compare($plugin_info['version'], '2.10', '<'))
		{
			return false;
		}

		return true;
	}

	public function isPluginEnabled()
	{
		return $this->isPluginInstalled()
			&& shopSeofilterBasicSettingsModel::getSettings()->is_enabled;
	}

	/**
	 * @return shopSeofilterFiltersFrontendStorage|null
	 */
	public function getFilterStorage()
	{
		return $this->filter_storage;
	}

	private function createFilterStorageInstance()
	{
		return $this->isPluginInstalled()
			? new shopSeofilterFiltersFrontendStorage()
			: null;
	}
}