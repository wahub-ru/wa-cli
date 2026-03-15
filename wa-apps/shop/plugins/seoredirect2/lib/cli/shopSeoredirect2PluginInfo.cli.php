<?php

class shopSeoredirect2InfoPluginCli extends shopSeoredirect2PluginCli
{
	public function countAutoRedirects()
	{
		$shop_urls_model = new shopSeoredirect2ShopUrlsModel();
		$this->dumpc('Count items ' . $shop_urls_model->countAll());
	}

	public function countRedirects()
	{
		$redirect_model = new shopSeoredirect2RedirectModel();
		$this->dumpc('Count items ' . $redirect_model->countAll());
	}

	public function countErrors()
	{
		$error_storage = new shopSeoredirect2ErrorStorage();
		$this->dumpc('Count items ' . $error_storage->getCount());
	}
}