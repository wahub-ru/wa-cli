<?php

/**
 * SINGLETON SETTINGS
 *
 * @author Steemy, created by 21.07.2021
 */

class shopApiextensionPluginSettings
{
    private static $_instance = null;

    private $constClass = 'shopApiextensionPluginConst';
    private $namePlugin;
    private $settings = null;
    private $settingsDefault;
    private $appSettingsModel;

    public function __construct()
    {
        $constClass = new $this->constClass();
        $this->namePlugin = $constClass->getNamePlugin();
        $this->settingsDefault = $constClass->getSettingsDefault();
        $this->appSettingsModel = new waAppSettingsModel();
    }

    private function __clone () {}
    public function __wakeup () {}

    public static function getInstance()
    {
        if (self::$_instance != null) {
            return self::$_instance;
        }

        return new self();
    }


    /**
     * Магик метод геттер php
     * @param $property
     * @return mixed
     */
    public function __get($property)
    {
        if (property_exists($this, $property)) {
            return $this->$property;
        }
    }


    /**
     * Получаем настройки.
     * @return array
     */
    public function getSettings()
    {
        if ($this->settings === null) {
            $this->settings = $this->appSettingsModel->get(array('shop', $this->namePlugin));
            foreach($this->settings as $key=>$value) {
                if (!is_numeric($value)) {
                    $json = json_decode($value, true);
                    if (is_array($json)) {
                        $this->settings[$key] = $json;
                    }
                }
            }
        }

        $this->getSettingsCheck($this->settings);
        return $this->settings;
    }


    /**
     * Получаем настройки и делаем проверку статуса, если удачно, то возвращаем массив настроек.
     * @throws waException
     * @return array
     */
    public function getSettingsCheckStatus()
    {
        return $this->checkStatusException($this->getSettings());
    }


    /**
     * Берем настройки и проверям, если не существует, то устнанавливаем по умолчанию
     * @param array $settings
     */
    public function getSettingsCheck(&$settings)
    {
        foreach($this->settingsDefault as $key=>$value)
        {
            if(empty($settings[$key]))
            {
                $settings[$key] = $value;
            }
            elseif(is_array($value))
            {
                foreach($settings[$key] as $k=>$v) {
                    if(empty($settings[$key][$k]))
                        $settings[$key][$k] = $value[$k];
                }
            }
        }
    }


    /**
     * Проверяем статус, если статус не активен то выбрасываем исключенеие и возваращаем страницу 404
     * @throws waException
     * @return array
     */
    private function checkStatusException($settings)
    {
        if(empty($settings['status']) || !$settings['status'])
        {
            throw new waException('Page not found', 404);
        }

        return $settings;
    }
}