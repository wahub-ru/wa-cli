<?php


class shopIpComplexCityApi implements shopIpCityApi
{
	/** @var shopIpCityApi[]  */
	private $city_api_collection = array();

	/**
	 * @param shopIpCityApi[] $city_api_collection
	 */
	public function __construct(array $city_api_collection)
	{
		foreach ($city_api_collection as $city_api)
		{
			if (!$city_api instanceof shopIpCityApi) {
				continue;
			}

			$this->city_api_collection[] = $city_api;
		}
	}

	public function getCities(shopIpCityCondition $condition, $limit)
	{
		$cities = array();

		foreach ($this->city_api_collection as $city_api)
		{
			$city_api_cities = $city_api->getCities($condition, $limit);
			$cities = array_merge($cities, $city_api_cities);

			$limit = $limit - count($city_api_cities);
		}

		return $cities;
	}
}
