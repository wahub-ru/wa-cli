<?php

class shopSeoredirect2Url
{
	protected $url;

	protected $parse;

	public function __construct($url = null)
	{
		$this->setUrl($url);
	}

	public function __toString()
	{
		return $this->url ? $this->url : '';
	}

	/**
	 * @param string $extra
	 * @return string
	 */
	final public static function getFullUrl($extra = '')
	{
		$server = waRequest::server();
		$url  = waRequest::isHttps() ? 'https://':  'http://';
		$url .= !empty($server['HTTP_HOST']) ? $server['HTTP_HOST'] : $server['SERVER_NAME'];
		//$url .= ( $server["SERVER_PORT"] != 80 ) ? ":".$server["SERVER_PORT"] : "";
        if(class_exists('shopSeofilterRouting') &&  shopSeofilterRouting::instance()->getOriginalRequestUri()) {
            $url .= shopSeofilterRouting::instance()->getOriginalRequestUri();
        } else {
            $url .= $server["REQUEST_URI"];
        }
		return $url . $extra;
	}

	final public function getParseUrl()
	{
		if (empty($this->parse))
		{
			$this->parse = parse_url($this->url);
		}

		return $this->parse;
	}

	/**
	 * @return array
	 */
	final public function getExplodePath()
	{
		$url_parse = $this->getParseUrl();
		if (empty($url_parse['path']))
		{
			return array();
		}
		$path_array = explode('/', $url_parse['path']);

		return $path_array;
	}

	final public function getPath()
	{
		$url_parse = $this->getParseUrl();

		return empty($url_parse['path']) ? '/' : $url_parse['path'];
	}

	/**
	 * @return string
	 */
	final public function getUrl()
	{
		return $this->url;
	}

	/**
	 * @param string $url
	 * @return shopSeoredirect2Url
	 */
	final public function setUrl($url = null)
	{
		$this->url = $url == null
			? self::getFullUrl()
			: $url;
		$this->parse = array();

		return $this;
	}

	public function getQuery()
	{
		$url_parse = $this->getParseUrl();

		return empty($url_parse['query']) ? '' : '?' . $url_parse['query'];
	}

	public function getRequestUrl()
	{
		return $this->getPath() . $this->getQuery();
	}
}