<?php

class shopSeoredirect2WaRouting
{
	private $routing;

	public function __construct()
	{
		$this->routing = wa()->getRouting();
	}

	public function &getRouting()
	{
		return $this->routing;
	}

	public function getStorefronts()
	{
		$domains = $this->getDomains();
		$urls = array();

		foreach ($domains as $domain)
		{
			$routes = $this->routing->getRoutes($domain);
			foreach ($routes as $route)
			{
				if (!$this->routing->isAlias($domain) and isset($route['url']))
				{
					$urls[] = $domain . '/' . $route['url'];
				}
			}
		}

		return $urls;
	}
	
	public function getDomains()
	{
		$domains = $this->getRouting()->getDomains();
		
		return array_values($domains);
	}

	public function getShopStorefronts()
	{
		$domains = $this->routing->getByApp('shop');
		$urls = array();

		foreach ($domains as $domain => $routes)
		{
			foreach ($routes as $route)
			{
				if (!$this->routing->isAlias($domain) and isset($route['url']))
				{
					$urls[] = $domain . '/' . $route['url'];
				}
			}
		}

		return $urls;
	}

	public function getCurrentStorefront()
	{
		$route = $this->routing->getRoute();
		$domain = $this->routing->getDomain();

		return $domain . '/' . $route['url'];
	}

	/**
	 * @return array [domain=>routes[]]
	 */
	public function getRoutes($app = 'shop')
	{
		$domains = $this->getDomains();
		$result = array();

		foreach ($domains as $domain)
		{
			$routes = $this->routing->getRoutes($domain);
			$r = array();
			foreach ($routes as $route)
			{
				if (!$this->routing->isAlias($domain) and isset($route['url']) and $route['app'] == $app)
				{
					$r[] = $route;
				}
			}
			$result[$domain] = $r;
		}

		return $result;
	}
}