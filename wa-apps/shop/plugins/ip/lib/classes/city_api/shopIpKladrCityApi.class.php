<?php


class shopIpKladrCityApi implements shopIpCityApi
{
	private $kladr_api_cities_model;
	
	public function __construct(shopIpKladrApiCityModel $kladr_api_cities_model)
	{
		$this->kladr_api_cities_model = $kladr_api_cities_model;
	}
	
	public function getCities(shopIpCityCondition $condition, $limit)
	{
		$limit = min(100, $limit);
		
		if (!$limit || !$condition || ($condition->getCountry() && $condition->getCountry() != 'rus'))
		{
			return array();
		}
		
		$query = $this->kladr_api_cities_model->select("'rus' country_iso3, substr(region_id, 1, 2) region_code, {$this->kladr_api_cities_model->getTableName()}.*");
		
		if ($condition->getRegion())
		{
			$query->where('region_id = ?', str_pad($condition->getRegion(), 13, '0'));
		}
		
		if ($condition->getQuery())
		{
			$sql_query = $this->kladr_api_cities_model->escape($condition->getQuery());
			$query->where('lower(name) like ?', "%{$sql_query}%");
			$query->order("locate(\"{$sql_query}\", name) asc, type_short = 'г' desc");
		}
		else
		{
			$query->order("type_short = 'г' desc, name asc");
		}
		
		$query->limit($limit);
		
		return $query->fetchAll();
	}
}
