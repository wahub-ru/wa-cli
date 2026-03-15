<?php

class shopGetimagePluginSettings extends waPluginSettings
{
    public function getSettings($name = null, $default = null)
    {
        $settings = parent::getSettings($name, $default);
        // Установите значения по умолчанию, если их нет
        if (!isset($settings['default_font_size'])) {
            $settings['default_font_size'] = 20;
        }
        return $settings;
    }
}