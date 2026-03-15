<?php


interface shopIpGeoIpApi
{
	/**
	 * @param $ip
	 * @return shopIpGeoIpResult
	 */
	public function getByIp($ip);
	
	/**
	 * @return shopIpGeoIpResult
	 */
	public function getForCurrentIp();
}