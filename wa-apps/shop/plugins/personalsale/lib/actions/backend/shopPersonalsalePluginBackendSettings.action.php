<?php

class shopPersonalsalePluginBackendSettingsAction extends waViewAction
{
	public function execute() {
		$plugin = wa()->getPlugin('personalsale');
		$status = $plugin->getSettings('status');
		$this->view->assign('status', $status);
	}
}
