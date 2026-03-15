<?php

class shopGetimagePluginSettingsSaveController extends waJsonController
{
    public function execute()
    {
        $settings = waRequest::post('settings', array());

        try {
            // Используем класс настроек
            $settingsManager = new shopGetimagePluginSettings('getimage');
            $settingsManager->save($settings);

            $this->response = array(
                'status' => 'ok',
                'message' => 'Настройки сохранены!'
            );
        } catch (Exception $e) {
            $this->errors = array(
                'messages' => array('Ошибка: ' . $e->getMessage())
            );
        }
    }
}