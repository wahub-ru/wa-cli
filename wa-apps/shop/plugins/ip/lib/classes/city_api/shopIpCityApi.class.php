<?php


interface shopIpCityApi
{
	public function getCities(shopIpCityCondition $condition, $limit);
}