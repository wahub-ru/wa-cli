<?php


class shopIpPluginSettingsAction extends waViewAction
{
	private $app_settings_model;
	private $kladr_api_region_model;
	private $kladr_api_city_model;
	private $custom_city_model;
	private $country_model;
	private $region_model;
	
	public function __construct($params = null)
	{
		parent::__construct($params);
		
		$this->app_settings_model = new waAppSettingsModel();
		$this->kladr_api_region_model = new shopIpKladrApiRegionModel();
		$this->kladr_api_city_model = new shopIpKladrApiCityModel();
		$this->custom_city_model = new shopIpCustomCityModel();
		$this->country_model = new waCountryModel();
		$this->region_model = new waRegionModel();
	}
	public function execute()
	{
		$city_api_collection_json = $this->app_settings_model->get('shop.ip', 'city_api_collection');
		$city_api_collection = json_decode($city_api_collection_json, true);

		if($city_api_collection === null || json_last_error() !== JSON_ERROR_NONE) {
			$city_api_collection = array(
				'kladr'
			);
		}

		$kladr_settings = array(
			'last_update_datetime' => $this->app_settings_model->get('shop.ip', 'kladr_api_last_update_datetime'),
			'regions_in_base_count' => intval($this->kladr_api_region_model->countAll()),
			'cities_in_base_count' => intval($this->kladr_api_city_model->countAll())
		);
		$custom_cities = $this->custom_city_model->getAll();
		$countries = $this->country_model->allWithFav();

		$this->view->assign('state', array(
			'kladr_settings' => $kladr_settings,
			'custom_cities' => $custom_cities,
			'countries' => $countries,
			'city_api_collection' => $city_api_collection
		));

        $plugin = wa('shop')->getPlugin('ip');
        $this->view->assign('plugin_version', $plugin->getVersion());
        $this->view->assign('resource_base_url', wa()->getAppStaticUrl('shop') . 'plugins/ip/assets/');
        $this->view->assign('ui_version', wa('shop')->whichUI());
	}
}
