<?php
/*
 * @link https://warslab.ru/
 * @author waResearchLab
 * @Copyright (c) 2021 waResearchLab
 */

class shopHidsetPluginFeaturesSelectableTask extends shopHidsetPluginCli implements shopHidsetPluginCliTaskInterface
{
    public function run($params = null)
    {
        (new shopHidsetPluginRepair())->featuresSelectableAction();
    }

    public function getCommand(): string
    {
        return 'featuresSelectable';
    }

    public function getDescription(): string
    {
        $html = <<<HTML
Восстанавливает выбор значений характеристик для формирования артикулов у товаров в режиме "Выбор параметров"
<span class="small task-info"><a href="https://support.webasyst.ru/shop-script/20593/data-repair/#featuresSelectable" target="_blank"><i class="icon16 info"></i> </a></span>
HTML;
        return $html;
    }
}