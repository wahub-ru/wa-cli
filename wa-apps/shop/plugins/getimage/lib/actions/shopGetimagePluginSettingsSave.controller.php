<?php

class shopGetimagePluginSettingsSaveController extends waJsonController
{
    public function execute()
    {
        // Получаем настройки из POST-запроса
        $settings = waRequest::post('settings', array());

        try {
            // Получаем экземпляр плагина
            $plugin = waSystem::getInstance()->getPlugin('getimage');

            // Сохраняем настройки
            $plugin->saveSettings($settings);

            // Успешный ответ
            $this->response = array(
                'message' => 'Настройки успешно сохранены!',
            );
        } catch (Exception $e) {
            // Обработка ошибок
            $this->errors['messages'][] = 'Не удалось сохранить настройки: ' . $e->getMessage();
        }
    }
}