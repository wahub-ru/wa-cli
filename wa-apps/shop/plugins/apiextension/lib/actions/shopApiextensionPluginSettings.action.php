<?php

/**
 * Settings for plugin backend
 *
 * @author Steemy, created by 21.07.2021
 */

class shopApiextensionPluginSettingsAction extends waViewAction
{
    public function execute()
    {
        $pluginSetting = shopApiextensionPluginSettings::getInstance();

        $settings = $pluginSetting->getSettings();
        $pluginSetting->getSettingsCheck($settings);

        $modelCategory = new shopCategoryModel();
        $categories = $modelCategory->getFullTree(null, true);

        $this->view->assign("categories", $categories);
        $this->view->assign("settings", $settings);
    }
}