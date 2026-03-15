<?php

class shopSeoredirect2InstallPluginCli extends shopSeoredirect2PluginCli
{
	public function execute()
	{
		$url_archivator = new shopSeoredirect2UrlArchivator();
		$url_archivator->run();
	}
}