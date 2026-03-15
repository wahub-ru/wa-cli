<?php


class shopIpSimpleGeoIpResult implements shopIpGeoIpResult
{
	private $country;
	private $region;
	private $city;

	public function getCountry()
	{
		return $this->country;
	}

	public function setCountry($country)
	{
		$this->country = $country;
	}

	public function getRegion()
	{
		return $this->region;
	}

	public function setRegion($region)
	{
		$this->region = $region;
	}

	public function getCity()
	{
		return $this->city;
	}

	public function setCity($city)
	{
		$this->city = $city;
	}
}