<?php


class shopSeoredirect2Url2
{
	private $parsed_url;
	
	public static function parse($url)
	{
		$parsed_url = parse_url($url);
		
		if (!is_string($url) || !$parsed_url)
		{
			throw new waException('Invalid URL.');
		}
		
		return new shopSeoredirect2Url2($parsed_url);
	}

	public static function getCurrentUrl()
	{
		$current_url_raw = wa()->getConfig()->getCurrentUrl();

		if (class_exists('shopSeofilterRouting') && class_exists('shopSeofilterBasicSettingsModel'))
		{
			$seofilter_settings = shopSeofilterBasicSettingsModel::getSettings();
			$seofilter_routing = shopSeofilterRouting::instance();

			if ($seofilter_settings->is_enabled && $seofilter_routing->isSeofilterPage())
			{
				$url_before = wa()->getConfig()->getCurrentUrl();
				$seofilter_routing->restoreInitialUrl();
				$url_after = wa()->getConfig()->getCurrentUrl();

				$current_url_raw = $url_after;

				if ($url_before != $url_after)
				{
					$seofilter_routing->removeSeofilterSuffixFromUrl();
				}
			}
		}

		return self::parse($current_url_raw);
	}
	
	private function __construct($parsed_url)
	{
		$this->parsed_url = $parsed_url;
		
		if (!isset($this->parsed_url['host']))
		{
			$this->parsed_url['host'] = $this->getCurrentHost();
		}
		
		if (!isset($this->parsed_url['scheme']))
		{
			$this->parsed_url['scheme'] = $this->getCurrentScheme();
		}
	}
	
	public function __toString()
	{
		$scheme = isset($this->parsed_url["scheme"]) ? $this->parsed_url["scheme"] . ":" : "";
		$host = isset($this->parsed_url["host"]) ? rawurlencode($this->parsed_url["host"]) : "";
		$double_slash = strlen($host) ? "//" : "";
		$path = isset($this->parsed_url["path"]) ? $this->parsed_url["path"] : "";
		$query = isset($this->parsed_url["query"]) ? "?" . $this->parsed_url["query"] : "";
		
		return "{$scheme}{$double_slash}{$host}{$path}{$query}";
	}
	
	private function getCurrentHost()
	{
		$server = waRequest::server();
		
		return $server['SERVER_NAME'] ? $server['SERVER_NAME'] : $server['HTTP_HOST'];
	}
	
	private function getCurrentScheme()
	{
		return waRequest::isHttps() ? 'https':  'http';
	}
}
