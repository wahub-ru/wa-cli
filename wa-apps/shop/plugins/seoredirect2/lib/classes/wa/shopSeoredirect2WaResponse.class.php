<?php

class shopSeoredirect2WaResponse
{
	public static function redirect($url, $code = 302)
	{
		$response = wa()->getResponse();

		$response->addHeader('Cache-Control', 'no-store, must-revalidate');
		$from_url = wa()->getConfig()->getHostUrl(true) . wa()->getConfig()->getRequestUrl(false, false);

		if ($from_url == $url) {
			return;
		}

		$response->redirect($url, $code);
	}
}