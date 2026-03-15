<?php

/**
 * INSTALL
 *
 * @author Steemy, created by 17.08.2021
 */

/**
 * Default settings plugin
 */
$pluginSetting = shopApiextensionPluginSettings::getInstance();

$settings = array();
$pluginSetting->getSettingsCheck($settings);
$namePlugin = $pluginSetting->namePlugin;
$appSettingsModel = $pluginSetting->appSettingsModel;

foreach($settings as $key=>$value) {
    if(is_array($value)) {
        $value = json_encode($value);
    }
    $appSettingsModel->set(array('shop', $namePlugin), $key, $value);
}


// add additional fileds review
$model = new waModel();
try {
    $model->query('SELECT apiextension_experience FROM shop_product_reviews WHERE 0');
} catch(waDbException $e) {
    $model->query('ALTER TABLE `shop_product_reviews` ADD `apiextension_experience` TEXT NULL AFTER `text`');
}

try {
    $model->query('SELECT apiextension_dignity FROM shop_product_reviews WHERE 0');
} catch(waDbException $e) {
    $model->query('ALTER TABLE `shop_product_reviews` ADD `apiextension_dignity` TEXT NULL AFTER `text`');
}

try {
    $model->query('SELECT apiextension_limitations FROM shop_product_reviews WHERE 0');
} catch(waDbException $e) {
    $model->query('ALTER TABLE `shop_product_reviews` ADD `apiextension_limitations` TEXT NULL AFTER `text`');
}

try {
    $model->query('SELECT apiextension_recommend FROM shop_product_reviews WHERE 0');
} catch(waDbException $e) {
    $model->query('ALTER TABLE `shop_product_reviews` ADD `apiextension_recommend` INT(1) NULL AFTER `text`');
}

try {
    $model->query('SELECT apiextension_votes FROM shop_product_reviews WHERE 0');
} catch(waDbException $e) {
    $model->query('ALTER TABLE `shop_product_reviews` ADD `apiextension_votes` VARCHAR(50) NULL AFTER `text`');
}