<?php

class shopSeoredirect2Autoredirect
{
	protected $id;
	protected $parent_id;
	protected $type;
	protected $url;
	protected $full_url;
	protected $url_type;

	protected $can_be_catalogreviews;

	public function __construct($item, $can_be_catalogreviews = false)
	{
		$this->setItem($item);

		$this->can_be_catalogreviews = $can_be_catalogreviews;
	}

	/**
	 * @return int
	 */
	public function getId()
	{
		return $this->id;
	}

	/**
	 * @return int
	 */
	public function getParentId()
	{
		return $this->parent_id;
	}

	/**
	 * @return int
	 */
	public function getType()
	{
		return $this->type;
	}

	/**
	 * @return string
	 */
	public function getUrl()
	{
		return $this->url;
	}

	/**
	 * @return string
	 */
	public function getFullUrl()
	{
		return $this->full_url;
	}

	/**
	 * @param mixed $id
	 */
	public function setId($id)
	{
		$this->id = $id;
	}

	/**
	 * @param mixed $parent_id
	 */
	public function setParentId($parent_id)
	{
		$this->parent_id = $parent_id;
	}

	/**
	 * @param mixed $type
	 */
	public function setType($type)
	{
		$this->type = $type;
	}

	/**
	 * @param mixed $url
	 */
	public function setUrl($url)
	{
		$this->url = $url;
	}

	/**
	 * @param mixed $full_url
	 */
	public function setFullUrl($full_url)
	{
		$this->full_url = $full_url;
	}

	public function getUrlType()
	{
		return $this->url_type;
	}

	public function setUrlType($url_type)
	{
		$this->url_type = $url_type;
	}

	public function canBeCatalogreviewsUrl()
	{
		return $this->can_be_catalogreviews;
	}

	/**
	 * @param array|string|int $item
	 * @throws waException
	 */
	public function setItem($item)
	{
		if (is_array($item))
		{
			if (!isset($item['id']))
			{
				throw new waException('$item not have id');
			}
			if (!isset($item['type']))
			{
				throw new waException('$item not have type');
			}
			$this->setData(
				$item['id'],
				$item['type'],
				ifempty($item['parent_id'], 0),
				ifempty($item['url'], ''),
				ifempty($item['full_url'], ''),
				ifempty($item['url_type'], '')
			);
		}
		else
		{
			$this->setId($item);
		}
	}

	public function setData($id, $type, $parent_id = 0, $url = '', $full_url = '', $url_type = '')
	{
		$this->setId($id);
		$this->setType($type);
		$this->setParentId($parent_id);
		$this->setUrl($url);
		$this->setFullUrl($full_url);
		$this->setUrlType($url_type);
	}
}