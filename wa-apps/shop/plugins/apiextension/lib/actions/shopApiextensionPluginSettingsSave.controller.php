<?php

/**
 * SAVE SETTINGS
 *
 * @author Steemy, created by 21.07.2021
 */

class shopApiextensionPluginSettingsSaveController extends waJsonController
{
    public function execute()
    {
        $pluginSetting = shopApiextensionPluginSettings::getInstance();
        $namePlugin = $pluginSetting->namePlugin;

        $settings = waRequest::post('shop_plugins', array());
        $pluginSetting->getSettingsCheck($settings);

        try {
            $plugin = waSystem::getInstance()->getPlugin($namePlugin);
            $plugin->saveSettings($settings);
        } catch (Exception $e) {
            $this->errors['messages'][] = 'Не удается сохранить поля настроек';
        }
    }
}