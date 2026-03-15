<?php


class shopIpCustomCityApi implements shopIpCityApi
{
	private $custom_city_model;
	
	public function __construct(shopIpCustomCityModel $custom_city_model)
	{
		$this->custom_city_model = $custom_city_model;
	}
	
	public function getCities(shopIpCityCondition $condition, $limit)
	{
		$limit = min(100, $limit);
		
		if (!$limit || !$condition || ($condition->getCountry() && $condition->getCountry() != 'rus'))
		{
			return array();
		}
		
		$query = $this->custom_city_model->select('*');

		if($condition->getCountry())
		{
			$query->where('country_iso3 = ?', $condition->getCountry());
		}
		
		if ($condition->getRegion())
		{
			$query->where('region_code = ?', $condition->getRegion());
		}
		
		if ($condition->getQuery())
		{
			$sql_query = $this->custom_city_model->escape($condition->getQuery());
			$query->where('lower(name) like ?', "%{$sql_query}%");
			$query->order("locate(\"{$sql_query}\", name) asc");
		}
		else
		{
			$query->order("name asc");
		}
		
		$query->limit($limit);
		
		return $query->fetchAll();
	}
}
