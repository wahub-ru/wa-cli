<?php


class shopIpPluginKladrapiSyncController extends waLongActionController
{
	const STAGE_REGION = 'region';
	const STAGE_CITY = 'city';
	const STAGE_DONE = 'done';
	
	private $api;
	private $app_settings_model;
	private $kladr_api_region_model;
	private $kladr_api_city_model;
	
	public function __construct()
	{
		$this->api = shopIpPlugin::getContext()->getKladrApi();
		$this->app_settings_model = new waAppSettingsModel();
		$this->kladr_api_region_model = new shopIpKladrApiRegionModel();
		$this->kladr_api_city_model = new shopIpKladrApiCityModel();
		$this->app_settings_model = new waAppSettingsModel();
	}
	
	/**
	 * Initializes new process.
	 * Runs inside a transaction ($this->data and $this->fd are accessible).
	 */
	protected function init()
	{
		$regions_model = new waRegionModel();
		$regions = array_values($regions_model->getByCountry('rus'));
		
		$this->data['stage'] = self::STAGE_REGION;
		$this->data['regions'] = $regions;
		$this->data['regions_offset'] = 0;
		$this->data['cities_offset'] = 0;
	}
	
	/**
	 * Checks if there is any more work for $this->step() to do.
	 * Runs inside a transaction ($this->data and $this->fd are accessible).
	 *
	 * $this->getStorage() session is already closed.
	 *
	 * @return boolean whether all the work is done
	 */
	protected function isDone()
	{
		$is_done = $this->data['stage'] == self::STAGE_DONE;
		
		if ($is_done)
		{
			$this->app_settings_model->set('shop.ip', 'kladr_api_last_update_datetime', date('Y-m-d H:i:s'));
		}
		
		return $is_done;
	}
	
	/**
	 * Performs a small piece of work.
	 * Runs inside a transaction ($this->data and $this->fd are accessible).
	 * Should never take longer than 3-5 seconds (10-15% of max_execution_time).
	 * It is safe to make very short steps: they are batched into longer packs between saves.
	 *
	 * $this->getStorage() session is already closed.
	 * @return boolean false to end this Runner and call info(); true to continue.
	 */
	protected function step()
	{
		if ($this->data['stage'] == self::STAGE_REGION)
		{
			return $this->stepRegion();
		}
		elseif ($this->data['stage'] == self::STAGE_CITY)
		{
			return $this->stepCity();
		}
		else
		{
			return true;
		}
	}
	
	private function stepRegion()
	{
		if (!isset($this->data['regions'][$this->data['regions_offset']]))
		{
			$this->data['stage'] = self::STAGE_CITY;
			$this->data['regions_offset'] = 0;
			
			return true;
		}
		
		$region = $this->data['regions'][$this->data['regions_offset']];
		$this->syncRegion($region);
		$this->data['regions_offset'] = $this->data['regions_offset'] + 1;
		
		return true;
	}
	
	private function stepCity()
	{
		if (!isset($this->data['regions'][$this->data['regions_offset']]))
		{
			$this->data['stage'] = self::STAGE_DONE;
			$this->data['regions_offset'] = 0;
			
			return true;
		}
		
		$region = $this->data['regions'][$this->data['regions_offset']];
		$this->syncCities($region, $this->data['cities_offset'], $count_synced);
		
		if ($count_synced == 0)
		{
			$this->data['regions_offset'] = $this->data['regions_offset'] + 1;
			$this->data['cities_offset'] = 0;
		}
		else
		{
			$this->data['cities_offset'] = $this->data['cities_offset'] + $count_synced;
		}
		
		return true;
	}
	
	private function syncRegion($region)
	{
		$kladr_region_id = str_pad($region['code'], 13, '0');
		$response = $this->api->query(array('contentType' => 'region', 'regionId' => $kladr_region_id));
		
		if (!isset($response['result'][0]))
		{
			return;
		}
		
		$region = $response['result'][0];
		
		$this->kladr_api_region_model->replace(array(
			'id' => $region['id'],
			'name' => $region['name'],
			'zip' => $region['zip'],
			'type' => $region['type'],
			'type_short' => $region['typeShort'],
			'okato' => $region['okato'],
			'content_type' => $region['contentType'],
		));
	}
	
	private function syncCities($region, $offset, &$count_synced)
	{
		$count_synced = 0;
		$kladr_region_id = str_pad($region['code'], 13, '0');
		$response = $this->api->query(array('contentType' => 'city', 'regionId' => $kladr_region_id, 'offset' => $offset, 'withParent' => true));
		
		if (!isset($response['result']))
		{
			return;
		}
		
		foreach ($response['result'] as $city)
		{
			$region_id = $city['id'];
			
			foreach ($city['parents'] as $parent)
			{
				if ($parent['contentType'] == 'region')
				{
					$region_id = $parent['id'];
					break;
				}
			}
			
			$this->kladr_api_city_model->replace(array(
				'id' => $city['id'],
				'region_id' => $region_id,
				'name' => $city['name'],
				'zip' => $city['zip'],
				'type' => $city['type'],
				'type_short' => $city['typeShort'],
				'okato' => $city['okato'],
				'content_type' => $city['contentType'],
			));
		}
		
		$count_synced = count($response['result']);
	}
	
	/**
	 * Called when $this->isDone() is true
	 * $this->data is read-only, $this->fd is not available.
	 *
	 * $this->getStorage() session is already closed.
	 *
	 * @param $filename string full path to resulting file
	 * @return boolean true to delete all process files; false to be able to access process again.
	 */
	protected function finish($filename)
	{
		return !!waRequest::post('finish');
	}
	
	/** Called by a Messenger when the Runner is still alive, or when a Runner
	 * exited voluntarily, but isDone() is still false.
	 *
	 * This function must send $this->processId to browser to allow user to continue.
	 *
	 * $this->data is read-only. $this->fd is not available.
	 */
	protected function info()
	{
		echo json_encode(array(
			'process_id' => $this->processId,
			'ready' => $this->isDone(),
			'stage' => $this->data['stage'],
			'regions_offset' => $this->data['regions_offset'],
			'regions_count' => count($this->data['regions']),
			'regions_in_base_count' => intval($this->kladr_api_region_model->countAll()),
			'cities_offset' => $this->data['cities_offset'],
			'cities_in_base_count' => intval($this->kladr_api_city_model->countAll()),
			'last_update_datetime' => $this->app_settings_model->get('shop.ip', 'kladr_api_last_update_datetime'),
		));
	}
	
	protected function infoReady($filename)
	{
		$this->info();
	}
}