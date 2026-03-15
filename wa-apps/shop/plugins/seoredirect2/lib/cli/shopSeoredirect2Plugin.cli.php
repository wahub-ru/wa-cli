<?php

class shopSeoredirect2PluginCli extends waCliController
{
	protected $params = array();

	protected function help()
	{
		echo 'Help' . PHP_EOL;
	}

	public function version()
	{
		$plugin = shopSeoredirect2Plugin::getInstance();
		$this->dump($plugin->getVersion());
	}

	public function settings()
	{
		$plugin = shopSeoredirect2Plugin::getInstance();
		$settings = $plugin->getSettings();

		if (isset($this->params['s']))
		{
			if (isset($this->params['v']))
			{
				$settings[$this->params['s']] = $this->params['v'];
				$plugin->saveSettings($settings);
			}
			$setting = ifset($settings[$this->params['s']]);
			$this->dump($setting);
			return;
		}
		
		$this->dump($settings);
	}

	public function preExecute()
	{
		$this->params = waRequest::param();
	}

	public function execute()
	{
		if (key_exists('help', $this->params))
		{
			$this->help();
		}

		if (!count($this->params))
		{
			return;
		}

		if (method_exists($this, $this->params[0]))
		{
			call_user_func(array($this, $this->params[0]));
			
			return;
		}
		
		$this->help();
	}

	public function dump() {
		$args = func_get_args();
		call_user_func_array(array($this, 'dumpc'), $args);
	}

	public static function dumpc() {
		$args = func_get_args();
		foreach ($args as $data)
		{
			if(is_array($data)) {
				print_r($data);
			} elseif (is_object($data)) {
				var_dump($data);
			} else {
				echo $data . PHP_EOL;
			}
		}
	}

	public static function eol($count = 1)
	{
		for ($i = 0; $i < $count; $i++)
		{
			echo PHP_EOL;
		}
	}
}