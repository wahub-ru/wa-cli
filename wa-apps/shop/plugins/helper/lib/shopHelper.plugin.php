<?php

class shopHelperPlugin extends shopPlugin
{
	const DAY = 86400;
	const HALF_OF_DAY = 43200;
	const HOUR = 3600;

	private static $current_storefront;

	/**
	 * @return string
	 */
	private static function getCurrentStorefront()
	{
		if (!isset(self::$current_storefront))
		{
			$routing = wa()->getRouting();

			$route = $routing->getRoute();
			$domain = $routing->getDomain();

			self::$current_storefront = $domain . '/' . $route['url'];
		}

		return self::$current_storefront;
	}

	/**
	 * @param string $key
	 * @param int $ttl
	 * @return waiCache
	 */
	public static function buildCacheImplement($key, $ttl = self::DAY)
	{
		return new waSerializeCache("plugins/helper/{$key}", $ttl, 'shop');
	}

	/**
	 * @return waCache
	 */
	public static function buildFileCache()
	{
		$adapter = new waFileCacheAdapter(array());

		return new waCache($adapter, 'shop');
	}

	/**
	 * @param string $type
	 * @return waCache|null|false
	 */
	public static function buildCache($type = 'default')
	{
		return wa()->getConfig()->getCache($type);
	}

	/**
	 * @param string $hash
	 * @param int $ttl
	 * @return int
	 */
	public static function productsCount($hash = '', $ttl = self::HALF_OF_DAY)
	{
		$storefront = self::getCurrentStorefront();
		$md5_storefront = md5($storefront);

		$cache = self::buildCacheImplement("products_count/{$md5_storefront}/{$hash}", $ttl);

		if (!$cache->isCached())
		{
			$collection = new shopProductsCollection($hash);
			try
			{
				$count = $collection->count();
			}
			catch (waException $e)
			{
				$count = 0;
			}

			$cache->set($count);
		}

		return $cache->get();
	}
}
