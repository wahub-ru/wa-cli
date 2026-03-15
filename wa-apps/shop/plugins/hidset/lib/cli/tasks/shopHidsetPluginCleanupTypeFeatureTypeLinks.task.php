<?php

class shopHidsetPluginCleanupTypeFeatureTypeLinksTask extends shopHidsetPluginCli implements shopHidsetPluginCliTaskInterface
{
    public function run($params = null)
    {
        (new shopHidsetPluginRepair())->cleanupTypeFeatureTypeLinksAction();
    }
    public function getCommand(): string
    {
        return 'cleanupTypeFeatureTypeLinks';
    }

    public function getDescription(): string
    {
        return <<<HTML
Удаляет неиспользуемые записи из таблицы 'shop_type_features' для корректного отображения счетчиков в разделе Настройки - Типы и характеристики товаров 
HTML;
    }
}