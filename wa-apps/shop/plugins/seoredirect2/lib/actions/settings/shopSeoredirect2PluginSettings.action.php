<?php

class shopSeoredirect2PluginSettingsAction extends waViewAction
{
	public function execute()
	{
		$plugin = shopSeoredirect2Plugin::getInstance();
		$version = $plugin->getVersion();
		$routing = new shopSeoredirect2WaRouting();
		$domains = $routing->getDomains();
        $this->view->assign('ui_version', wa()->whichUI());
        $this->view->assign('plugin_version', $version);

		wa()->getView()->assign(array(
			'version' => $version,
			'domains' => $domains,
		));
	}
}