<?php
/*
 * @link https://warslab.ru/
 * @author waResearchLab
 * @Copyright (c) 2021 waResearchLab
 */

class shopHidsetPluginSkuTask extends shopHidsetPluginCli implements shopHidsetPluginCliTaskInterface
{
    public function run($params = null)
    {
        (new shopHidsetPluginRepair())->skuAction();
    }
    public function getCommand(): string
    {
        return 'sku';
    }

    public function getDescription(): string
    {
        $html = <<<HTML
Исправляет для товаров выбор артикула по умолчанию
<span class="small task-info"><a href="https://support.webasyst.ru/shop-script/20593/data-repair/#sku" target="_blank"><i class="icon16 info"></i> </a></span>
HTML;
        return $html;
    }
}