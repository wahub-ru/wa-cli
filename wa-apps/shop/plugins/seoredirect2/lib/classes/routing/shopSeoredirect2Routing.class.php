<?php

abstract class shopSeoredirect2Routing
{
	protected $routing = array();
	protected $path;

	public function __construct($path)
	{
		if (!empty($path))
		{
			$this->routing = include $path . '';
			$this->path = $path;
		}
	}

	public function __toString()
	{
		return $this->path ? $this->path : '';
	}

	/**
	 * @return array
	 */
	public function getRouting()
	{
		return $this->routing;
	}


}