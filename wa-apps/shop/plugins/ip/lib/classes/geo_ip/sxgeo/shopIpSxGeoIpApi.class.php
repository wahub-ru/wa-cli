<?php


class shopIpSxGeoIpApi implements shopIpGeoIpApi
{
	private $sx_geo;

	public function __construct(shopIpSxGeo $sx_geo)
	{
		$this->sx_geo = $sx_geo;
	}

	public function getByIp($ip)
	{
		$arr_result = $this->sx_geo->getCityFull($ip);

		if ($arr_result === false)
		{
			return null;
		}

		$country_iso = strtolower(ifset($arr_result['country']['iso']));

		$country_model = new waCountryModel();
		$country_row = $country_model->getByField('iso2letter', $country_iso);
		$country_row = $country_model->get($country_row['iso3letter']);

		$country_iso = ifset($country_row['iso3letter']);
		$region_name_ru = ifset($arr_result['region']['name_ru']);
		$region_name_en = ifset($arr_result['region']['name_en']);

		$region_model = new waRegionModel();
		$region_row = $region_model->getByField(array(
			'country_iso3' => $country_iso,
			'name' => array(
				$region_name_en,
				$region_name_ru,
			),
		));

		$city = ifset($arr_result['city']['name_ru']);

		$result = new shopIpSimpleGeoIpResult();
		$result->setCountry($country_row['iso3letter']);
		$result->setRegion(isset($region_row) ? $region_row['code'] : $region_name_ru);
		$result->setCity($city);

		return $result;
	}
	
	public function getForCurrentIp()
	{
		$ip = shopIpPlugin::getRequest()->getIp();
		
		return $this->getByIp($ip);
	}
}