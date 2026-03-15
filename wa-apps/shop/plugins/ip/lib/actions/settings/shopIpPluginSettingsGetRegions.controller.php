<?php


class shopIpPluginSettingsGetRegionsController extends waJsonController
{
	private $region_model;

	public function __construct()
	{
		$this->region_model = new waRegionModel();
	}

	public function execute()
	{
		$country_iso3 = waRequest::post('country_iso3');

		$this->response = $this->region_model->getByCountryWithFav($country_iso3);
	}
}
