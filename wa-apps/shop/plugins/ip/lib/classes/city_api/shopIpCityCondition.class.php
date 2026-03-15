<?php


class shopIpCityCondition
{
	private $country;
	private $region;
	private $query;
	
	public static function create()
	{
		return new self();
	}
	
	private function __construct()
	{
	}
	
	public function getCountry()
	{
		return $this->country;
	}
	
	public function setCountry($country)
	{
		$this->country = $country;
		
		return $this;
	}
	
	public function getRegion()
	{
		return $this->region;
	}
	
	public function setRegion($region)
	{
		$this->region = $region;
		
		return $this;
	}
	
	public function getQuery()
	{
		return $this->query;
	}
	
	public function setQuery($query)
	{
		$this->query = mb_strtolower($query);
		
		return $this;
	}
}