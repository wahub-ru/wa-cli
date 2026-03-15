<?php


class shopIpKladrApiImpl implements shopIpKladrApi
{
	public function query($params)
	{
		$query = http_build_query($params);
		
		$curl = curl_init();
		curl_setopt_array($curl, array(
			CURLOPT_URL => "https://kladr-api.ru/api.php?{$query}",
			CURLOPT_RETURNTRANSFER => true,
			CURLOPT_TIMEOUT => 10,
		));
		$response_json = curl_exec($curl);

		return json_decode($response_json, true);
	}
}