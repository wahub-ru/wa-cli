<?php
/*
 * @link https://warslab.ru/
 * @author waResearchLab
 * @Copyright (c) 2021 waResearchLab
 */

class shopHidsetPluginProductRemoveFeaturesSelectableTask extends shopHidsetPluginCli implements shopHidsetPluginCliTaskInterface
{
    public function run($params = null)
    {
        (new shopHidsetPluginRepair())->productRemoveFeaturesSelectableAction();
    }

    public function getCommand(): string
    {
        return 'productRemoveFeaturesSelectable';
    }

    public function getDescription(): string
    {
        $html = <<<HTML
Удаляет лишние записи о значениях характеристик, которые используются для формирования артикулов товара, из свойств товара — такие характеристики должны быть связаны только с артикулами товара, а не с самим товаром. Это исправление имеет смысл, только если товары продаются в режиме «Выбор параметров»
<span class="small task-info"><a href="https://support.webasyst.ru/shop-script/20593/data-repair/#productRemoveFeaturesSelectable" target="_blank"><i class="icon16 info"></i> </a></span>
HTML;
        return $html;
    }
}