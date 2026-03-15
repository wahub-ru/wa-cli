<?php

class shopGetimagePluginSettingsAction extends waViewAction
{
    public function execute()
    {
        $settingsManager = new shopGetimagePluginSettings('getimage');
        $settings = $settingsManager->getSettings();

        // Передаем настройки в шаблон
        $this->view->assign('settings', $settings);

        // Генерация элементов управления
        $controls = array();
        foreach ($settings as $key => $data) {
            $controls[$key] = waHtmlControl::getControl($data['control_type'], $key, $data);
        }
        $this->view->assign('settings_controls', $controls);
    }
}