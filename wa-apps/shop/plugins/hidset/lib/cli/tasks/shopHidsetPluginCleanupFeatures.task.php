<?php
/*
 * @link https://warslab.ru/
 * @author waResearchLab
 * @Copyright (c) 2021 waResearchLab
 */

class shopHidsetPluginCleanupFeaturesTask extends shopHidsetPluginCli implements shopHidsetPluginCliTaskInterface
{
    public function run($params = null)
    {
        (new shopHidsetPluginRepair())->cleanupFeaturesAction();
    }

    public function getCommand(): string
    {
        return 'cleanupFeatures';
    }

    public function getDescription(): string
    {
        $html = <<<HTML
Удаляет лишние записи о значениях характеристик, связанных с товарами и не связанных с артикулами товаров. Полезно для исправления отображения лишних значений характеристик, которых не видно при редактировании товаров
<span class="small task-info"><a href="https://support.webasyst.ru/shop-script/20593/data-repair/#cleanupFeatures" target="_blank"><i class="icon16 info"></i> </a></span>
HTML;
        return $html;
    }
}