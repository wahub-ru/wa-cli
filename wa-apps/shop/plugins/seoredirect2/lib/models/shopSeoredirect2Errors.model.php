<?php

class shopSeoredirect2ErrorsModel extends waModel
{
	protected $table = 'shop_seoredirect2_errors';

	public function addError($domain, $url, $error_code)
	{
		if (preg_match('~.*/ordercall-config/$~', $url))
		{
			return;
		}

		$hash = $this->getHashByDomainAndUrl($domain, $url);
		$error = $this->getByField(array(
			'hash' => $hash,
			'domain' => $domain,
			'url' => $url,
		));
		$error_id = null;

		if (!empty($error))
		{
			$this->updateById($error['id'], array(
				'http_referer' => waRequest::server('HTTP_REFERER'),
				'views' => $error['views'] + 1,
				'edit_datetime' => date('Y-m-d H:i:s'),
			));
			$error_id =$error['id'];
		}
		else
		{
			$data = array(
				'hash' => $hash,
				'domain' => $domain,
				'url' => $url,
				'http_referer' => waRequest::server('HTTP_REFERER'),
				'code' => $error_code,
				'views' => 1,
				'create_datetime' => date('Y-m-d H:i:s'),
				'edit_datetime' => date('Y-m-d H:i:s'),
			);
			$error_id = $this->insert($data);
		}
		return $error_id;
	}

	private function getHashByDomainAndUrl($domain, $url)
	{
		return md5($domain . $url);
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
}
