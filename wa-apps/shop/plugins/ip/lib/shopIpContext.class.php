<?php


class shopIpContext
{
	private $geo_ip_api;
	private $request;
	private $city_api;
	private $kladr_api;
	private $plugin;

	/**
	 * @param shopIpPlugin $plugin
	 * @throws waException
	 */
	public function __construct(shopIpPlugin $plugin)
	{
		$this->plugin = $plugin;

		$sx_geo_dat_path = wa()->getAppPath('plugins/ip/lib/vendor/geo_ip/sx_geo/SxGeo.dat', 'shop');
		$sx_geo = new shopIpSxGeo($sx_geo_dat_path, shopIpSxGeo::SXGEO_BATCH);
		$this->geo_ip_api = new shopIpSxGeoIpApi($sx_geo);

		$this->request = new shopIpWaRequest();

		$this->kladr_api = new shopIpKladrApiImpl();

		$city_api_collection = $this->getCityApiCollection();
		$this->city_api = new shopIpComplexCityApi($city_api_collection);
	}

	/**
	 * @return shopIpCityApi[]
	 */
	private function getCityApiCollection()
	{
		$setting = $this->plugin->getSettings('city_api_collection');
		$city_api_collection = array();

		foreach ($setting as $city_api_name)
		{
			if ($city_api_name === 'kladr')
			{
				$city_api_collection[] = new shopIpKladrCityApi(new shopIpKladrApiCityModel());
			}
			elseif ($city_api_name === 'custom')
			{
				array_unshift($city_api_collection, new shopIpCustomCityApi(new shopIpCustomCityModel()));
			}
		}

		return $city_api_collection;
	}
	
	/**
	 * @return shopIpGeoIpApi
	 */
	public function getGeoIpApi()
	{
		return $this->geo_ip_api;
	}
	
	/**
	 * @return shopIpRequest
	 */
	public function getRequest()
	{
		return $this->request;
	}
	
	/**
	 * @return shopIpCityApi
	 */
	public function getCityApi()
	{
		return $this->city_api;
	}
	
	/**
	 * @return shopIpKladrApi
	 */
	public function getKladrApi()
	{
		return $this->kladr_api;
	}
}
