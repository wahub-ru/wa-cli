<?php

class shopSeoredirect2RedirectModel extends waModel
{
	const GENERAL_DOMAIN = 'general';
	protected $table = 'shop_seoredirect2_redirect';
	protected $cache;

	public function __construct($type = null, $writable = false)
	{
		parent::__construct($type, $writable);
		$this->cache = $this->newCache();
	}

	public function newCache()
	{
		return new waSerializeCache('redirects', -1, 'shop_seoredirect2');
	}

	public function getCache()
	{
		if (is_null($this->cache))
		{
			return $this->newCache();
		}
		return $this->cache;
	}

	public function getByDomainAndUrl($domain, $url)
	{
        if ($this->isUtf8mb4($url)) {
            return null;
        }

		$hash = $this->getHashByDomainAndUrl($domain, $url);
		$redirect = $this->getByField(array(
			'hash' => $hash,
			'domain' => $domain,
			'url_from' => $url,
		));

		if (!$redirect)
		{
			$hash = $this->getHashByDomainAndUrl(self::GENERAL_DOMAIN, $url);
			$redirect = $this->getByField(array(
				'hash' => $hash,
				'domain' => self::GENERAL_DOMAIN,
				'url_from' => $url,
			));
		}

		return $redirect;
	}

	public function getHashByDomainAndUrl($domain, $url)
	{
		return md5($domain . $url);
	}

	public function hasRedirect($domain, $url)
	{
		if (empty($url))
		{
			return 0;
		}

		return !!$this->countByField(array(
			'domain' => array(self::GENERAL_DOMAIN, $domain),
			'url_from' => $url,
		));
	}

	public function getAllSorted($order='ASC', $key = null, $normalize = false)
	{
		return $this->select('*')->order('sort '. $order)->fetchAll($key, $normalize);
	}

	/**
	 * @param null|string $field
	 * @return array|string
	 */
	public function getLast($field = null)
	{
		$redirect = $this->select('*')->order('sort DESC')->fetchAssoc();
		if (is_null($field))
		{
			return $redirect;
		}
		return isset($redirect[$field]) ? $redirect[$field] : $redirect;
	}

	public function getLastSort()
	{
		$last = $this->getLast('sort');
		if (is_null($last))
		{
			$last = 0;
		}
		else
		{
			$last = intval($last) + 1;
		}

		return $last;
	}

	public function newSortingByIds($ids = array())
	{
		$redirects = $this->getByField('id', $ids, true);
		if(!count($redirects))
		{
			return;
		}
		$min = self::getMinByRedirects($redirects);
		foreach ($ids as $id)
		{
			$this->updateById($id, array('sort' => $min));
			$min++;
		}
		$this->getCache()->delete();
	}

	public function newSorting()
	{
		foreach ($this->getAllSorted() as $key => $redirect)
		{
			$this->updateById($redirect['id'], array('sort'=> $key));
		}
		$this->getCache()->delete();
	}

	public function deleteById($value)
	{
		$result =  parent::deleteById($value);
		$this->newSorting();

		return $result;
	}

	public function deleteByField($field, $value = null)
	{
		$result =  parent::deleteByField($field, $value);
		$this->newSorting();

		return $result;
	}

	public function parentDeleteByField($field, $value = null)
	{
		return parent::deleteByField($field, $value);
	}

	public function updateById($id, $data, $options = null, $return_object = false)
	{
		$res = parent::updateById($id, $data, $options, $return_object);
		$this->getCache()->delete();
		return $res;
	}

	public function updateByField($field, $value, $data = null, $options = null, $return_object = false)
	{
		$res = parent::updateByField(
			$field,
			$value,
			$data,
			$options,
			$return_object
		);

		$this->getCache()->delete();
		return $res;
	}


	public static function getMinByRedirects($redirects)
	{
		if (!is_array($redirects))
		{
			return null;
		}
		if (!count($redirects))
		{
			return null;
		}
		$current = current($redirects);
		$min = intval($current['sort']);
		foreach ($redirects as $redirect)
		{
			$min = min(intval($redirect['sort']), $min);
		}

		return $min;
	}

	public function addRedirect($redirect)
	{
		$domain = ifset($redirect['domain']);
		$url = ifset($redirect['url_from']);
		$hash = $this->getHashByDomainAndUrl($domain, $url);

		$redirect['hash'] = $hash;
		$redirect['edit_datetime'] = date('Y-m-d H:i:s');
		$redirect['comment'] = ifset($redirect['comment'], '');
		$redirect['type'] = self::getType($url);

		if (isset($redirect['id']))
		{
			return $this->updateById($redirect['id'], $redirect);
		}
		else
		{
			parent::deleteByField(array(
				'hash' => $hash,
				'domain' => $domain,
				'url_from' => $url,
			));
			$redirect['create_datetime'] = date('Y-m-d H:i:s');
			$last = $this->getLastSort();
			$redirect['sort'] = $last;
			return $this->insert($redirect);
		}
	}

	public function addRedirects($redirects)
	{
		$last = $this->getLastSort();

		foreach ($redirects as $key => $redirect)
		{
			$domain = ifset($redirect['domain']);
			$url = ifset($redirect['url_from']);
			$hash = $this->getHashByDomainAndUrl($domain, $url);

			$redirects[$key]['hash'] = $hash;
			$redirects[$key]['type'] = self::getType($url);
			$redirects[$key]['sort'] = $last + $key;
			parent::deleteByField(array(
				'hash' => $hash,
				'domain' => $domain,
				'url_from' => $url,
			));
		}
		$split_redirects = array();
		$limit = 200;
		$i = 0;
		foreach ($redirects as $redirect)
		{
			$split_redirects[] = $redirect;
			$i++;
			if ($i == $limit)
			{
				$this->multipleInsert($split_redirects);
				$split_redirects = array();
				$i = 0;
			}
		}
		if (count($split_redirects))
		{
			$this->multipleInsert($split_redirects);
		}
		$this->newSorting();
	}

	/**
	 * Возвращает все отсортированные редиректы
	 *
	 * @return array|null
	 */
	public function getRedirects()
	{
		if ($this->getCache()->isCached())
		{
			return $this->getCache()->get();
		}
		$redirects = $this->getAllSorted();
		$this->getCache()->set($redirects);

		return $redirects;
	}

	public static function getType($url)
	{
		return shopSeoredirect2Redirect::type($url);
	}

	public static function isReg($url)
	{
		return shopSeoredirect2Redirect::isReg($url);
	}

	/**
	 * @param $id int|array
	 */
	public function delete($id)
	{
		if (!$id)
		{
			return;
		}
		if (!is_array($id))
		{
			$this->deleteById($id);
		}
		else
		{
			$this->deleteByField('id', $id);
		}
	}

	public function deleteAll()
	{
		$this->exec("DELETE FROM `{$this->table}`");
	}

    private function isUtf8mb4($str) {
        return max(array_map('ord', str_split($str))) >= 240;
    }
}
