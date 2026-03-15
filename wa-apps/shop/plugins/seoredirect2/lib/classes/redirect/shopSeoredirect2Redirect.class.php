<?php

class shopSeoredirect2Redirect
{
	const GENERAL = 'general';
	const TYPE_SIMPLE = 0;
	const TYPE_REGULAR = 1;
	const STATUS_OFF = 0;
	const STATUS_ON = 1;

	protected $matches;

	protected $id;
	protected $domain = self::GENERAL;
	protected $url_from;
	protected $url_to;
	protected $param = self::STATUS_OFF;
	protected $type = self::TYPE_SIMPLE;
	protected $status = self::STATUS_OFF;
	protected $sort;
	protected $code_http = 301;
	protected $comment = '';
	protected $create_datetime;
	protected $edit_datetime;

	/**
	 * @var shopSeoredirect2Url
	 */
	protected $url;

	public function __construct($redirect_data = array())
	{
		$this->setData($redirect_data);
	}

	protected function getStaticData()
	{
		return array(
			'id',
			'domain',
			'url_from',
			'url_to',
			'param',
			'type',
			'status',
			'sort',
			'code_http',
			'comment',
			'create_datetime',
			'edit_datetime'
		);
	}

	public function setData($redirect_data)
	{
		$data = $this->getStaticData();
		foreach ($redirect_data as $name => $value)
		{
			if (in_array($name, $data) and property_exists($this, $name))
			{
				$this->{$name} = $value;
			}
		}
	}

	public function getData()
	{
		$data = array();
		foreach ($this->getStaticData() as $name)
		{
			if ($this->{$name} !== null)
			{
				$data[$name] = $this->{$name};
			}
		}

		return $data;
	}

	public function getHash()
	{
		return md5($this->domain, $this->url_from);
	}

	public function getModel()
	{
		static $redirect_model;
		if (empty($redirect_model))
		{
			$redirect_model = new shopSeoredirect2RedirectModel();
		}

		return $redirect_model;
	}

	public function save()
	{
		if ($this->url_from)
		{
			if ($this->getId())
			{
				$this->update();
			}
			else
			{
				$this->add();
			}
		}
	}

	protected function add()
	{
		$data = $this->getData();
		$this->getModel()->addRedirect($data);
	}

	protected function update()
	{
		$data = $this->getData();
		$id = $data['id'];
		unset($data['id']);
		$this->getModel()->updateById($id, $data);
	}

	public static function isUrl($url)
	{
		return filter_var($url, FILTER_VALIDATE_URL);
	}

	public static function isReg($url)
	{
		$pos = strpos($url, '*');

		if ($pos === false)
		{
			$pos = strpos($url, '$');
		}
		if ($pos === false)
		{
			$pos = strpos($url, '\?');
		}
		if ($pos === false)
		{
			return false;
		}

		return true;
	}

	public static function type($url)
	{
		if (self::isReg($url))
		{
			return self::TYPE_REGULAR;
		}
		else
		{
			return self::TYPE_SIMPLE;
		}
	}

	public function isGeneral()
	{
		return $this->domain == self::GENERAL;
	}

	public function isActive()
	{
		return (bool)$this->status;
	}

	public function deactivate()
	{
		$this->status = false;
	}

	public function isParam()
	{
		return (bool)$this->param;
	}

	/**
	 * @param string $domain
	 * @return $this
	 */
	public function setDomain($domain)
	{
		$this->domain = $domain;

		return $this;
	}

	public function getDomain()
	{
		if ($this->isGeneral())
		{
			return wa()->getRouting()->getDomain();
		}

		return $this->domain;
	}

	public function getUrlFrom()
	{
		return $this->url_from;
	}

	/**
	 * @param mixed $url_from
	 * @return $this
	 */
	public function setUrlFrom($url_from)
	{
		$this->url_from = $url_from;

		return $this;
	}

	public function getUrlTo()
	{
		$url_to = $this->url_to;
		if (self::isReg($url_to))
		{
			preg_match_all('#\$(\d+)#is', $url_to, $blocks);
			$replace_blocks = array();
			foreach ($blocks[1] as $num) {
				$replace_blocks[] = $this->matches[(int)$num];
			}
			$url_to = str_replace($blocks[0], $replace_blocks, $url_to);
		}

		if (self::isUrl($url_to) === false)
		{
			$domain = $this->getDomain();
            $protocol = waRequest::isHttps() ? 'https' : 'http';
			$url_to = "{$protocol}://" . str_replace('*', '', $domain) . $url_to;
		}

		if ($this->isParam())
		{
			$has_query = strpos($url_to, '?');
			if ($has_query === false)
			{
				$url_to = $url_to . $this->url->getQuery();
			}
		}
		//elseif ($this->url->getQuery())
		//{
		//	return null;
		//}

		return $url_to;
	}

	/**
	 * @param mixed $url_to
	 * @return $this
	 */
	public function setUrlTo($url_to)
	{
		$this->url_to = $url_to;

		return $this;
	}

	/**
	 * @return int
	 */
	public function getCodeHttp()
	{
		return $this->code_http;
	}

	/**
	 * @param int $code_http
	 * @return $this
	 */
	public function setCodeHttp($code_http)
	{
		$this->code_http = $code_http;

		return $this;
	}

	/**
	 * @return mixed
	 */
	public function getCreateDatetime()
	{
		return $this->create_datetime;
	}

	/**
	 * @return mixed
	 */
	public function getEditDatetime()
	{
		return $this->edit_datetime;
	}

	/**
	 * @param string $comment
	 * @return $this
	 */
	public function setComment($comment)
	{
		$this->comment = $comment;

		return $this;
	}

	/**
	 * @return string
	 */
	public function getComment()
	{
		return $this->comment;
	}

	public function redirect()
	{
		$url_to = $this->getUrlTo();
		if (is_null($url_to))
		{
			return;
		}

		shopSeoredirect2WaResponse::redirect($url_to, $this->getCodeHttp());
	}

	public function equal(self $redirect)
	{
		return $this->getHash() == $redirect->getHash();
	}

	public function equalUrlFrom()
	{
		$url_from = $this->getUrlFrom();
		$url_from = str_replace('/', '\/', $url_from);
		$url_from = str_replace('*', '(.*)', $url_from);

		$pos = strpos($url_from, '^');

		if ($pos === false)
		{
			$url = $this->url->getUrl();
		}
		else
		{
			$url = $this->url->getRequestUrl();
		}
		$url = urldecode($url);

		return !!preg_match("/{$url_from}/", $url, $this->matches);
	}

	public function equalDomain($domain)
	{
		return $this->getDomain() == $domain;
	}

	public function setUrl(shopSeoredirect2Url $url)
	{
		$this->url = $url;

		return $this;
	}

	/**
	 * @return shopSeoredirect2Url
	 */
	public function getUrl()
	{
		return $this->url;
	}

	/**
	 * @return mixed
	 */
	public function getId()
	{
		return $this->id;
	}

	/**
	 * @return int
	 */
	public function getType()
	{
		return $this->type;
	}

	/**
	 * @param int $type
	 * @return $this
	 */
	public function setType($type)
	{
		$this->type = $type;

		return $this;
	}
}