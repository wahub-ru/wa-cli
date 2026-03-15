<?php


class shopIpPluginSettingsSaveCitiesController extends waJsonController
{
	private $custom_city_model;

	public function __construct()
	{
		$this->custom_city_model = new shopIpCustomCityModel();
	}

	public function execute()
	{
		$cities = waRequest::post('cities', array(), waRequest::TYPE_ARRAY);

		$this->response = $this->custom_city_model->setCities($cities);
	}
}
