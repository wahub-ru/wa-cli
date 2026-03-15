<?php


class shopIpCustomCityModel extends waModel
{
	protected $table = 'shop_ip_custom_city';

	/**
	 * @param array $cities
	 * @return bool|resource
	 * @throws waException
	 */
	public function setCities(array $cities)
	{
		$this->truncate();

		return $this->multipleInsert($cities);
	}
}
