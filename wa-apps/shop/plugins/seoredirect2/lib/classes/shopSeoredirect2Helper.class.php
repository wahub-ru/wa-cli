<?php

class shopSeoredirect2Helper
{
	public static function seofilterExists()
	{
		return class_exists('shopSeofilterPlugin');
	}

	public static function seofilterVersion()
	{
		if (!self::seofilterExists())
		{
			return null;
		}
		static $version;
		if (empty($version))
		{
			$version = wa('shop')->getPlugin('seofilter')->getVersion();
		}

		return $version;
	}

	public static function seofilterOver2()
	{
		if (!self::seofilterExists())
		{
			return false;
		}

		return self::seofilterVersion() >= 2;
	}

	/**
	 * @return shopSeofilterPluginSettings|null
	 */
	public static function getSeofilterSettings()
	{
		return self::seofilterOver2()
			? shopSeofilterBasicSettingsModel::getSettings()
			: null;
	}

	public static function minPage($count_on_page, $page, $count_all)
	{
		while ($count_on_page * ($page - 1) >= $count_all)
		{
			if ($page == 1)
			{
				break;
			}
			$page--;
		}

		return $page;
	}
}
