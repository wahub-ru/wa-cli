<?php
/**
 * До версии 2.0 МультиСтраны
 *
 * User: Echo-company
 * Email: info@echo-company.ru
 * Site: https://www.echo-company.ru
 */

$model = new waModel();

try {

    //Создаем таблицу shop_cityselect__regions_iso
    $sql = "CREATE TABLE IF NOT EXISTS `shop_cityselect__regions_iso` (
              `id` int NOT NULL AUTO_INCREMENT,
              `country_iso3` varchar(64) NULL,  
              `region_code` varchar(64) NULL,              
              `region_iso` varchar(64) NULL,              

              PRIMARY KEY (`id`),
              KEY `region` (`country_iso3`, `region_code`),
              KEY `iso` (`region_iso`)
            ) DEFAULT CHARSET=utf8;";
    $model->exec($sql);
} catch (waDbException $e) {
}

//Обновление настроек
$settings = $this->getSettings();

foreach ($settings['routes'] as $key => $route_setting) {
    $settings['routes'][$key]['language'] = 'ru';
    $settings['routes'][$key]['countries'] = '';
    $settings['routes'][$key]['default_country'] = 'rus';
}

$this->saveSettings($settings);