<?php

class shopSeoredirect2DeletePluginCli extends shopSeoredirect2PluginCli
{
	public function autoRedirects()
	{
		$shop_urls_model = new shopSeoredirect2ShopUrlsModel();
		$shop_urls_model->query('TRUNCATE TABLE ' . $shop_urls_model->getTableName());
	}

	public function redirects()
	{
		$redirect_model = new shopSeoredirect2RedirectModel();
		$redirect_model->query('TRUNCATE TABLE ' . $redirect_model->getTableName());
	}

	public function errors()
	{
		$error_storage = new shopSeoredirect2ErrorStorage();
		$error_storage->clean();
	}
}