<?php


class shopIpPlugin extends shopPlugin
{
	private static $context;
	
	public static function getContext()
	{
		if (!isset(self::$context))
		{
			$plugin = wa('shop')->getPlugin('ip');
			self::$context = new shopIpContext($plugin);
		}
		
		return self::$context;
	}
	
	public static function getGeoIpApi()
	{
		return self::getContext()->getGeoIpApi();
	}
	
	public static function getRequest()
	{
		return self::getContext()->getRequest();
	}
	
	public static function getCityApi()
	{
		return self::getContext()->getCityApi();
	}
}
