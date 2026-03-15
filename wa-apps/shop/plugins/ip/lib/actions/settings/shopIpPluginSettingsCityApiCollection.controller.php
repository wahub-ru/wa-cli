<?php


class shopIpPluginSettingsCityApiCollectionController extends waJsonController
{
	private $app_settings_model;

	public function __construct()
	{
		$this->app_settings_model = new waAppSettingsModel();
	}

	public function execute()
	{
		$city_api_collection = waRequest::post('city_api_collection', array(), waRequest::TYPE_ARRAY);

		$this->app_settings_model->set('shop.ip', 'city_api_collection', json_encode($city_api_collection));
	}
}
