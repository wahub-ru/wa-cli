<?php
/*
 * @link https://warslab.ru/
 * @author waResearchLab
 * @Copyright (c) 2021 waResearchLab
 */

class shopHidsetPluginProductCountsTask extends shopHidsetPluginCli implements shopHidsetPluginCliTaskInterface
{
    public function run($params = null)
    {
        (new shopHidsetPluginRepair())->productCountsAction();
    }

    public function getCommand(): string
    {
        return 'productCounts';
    }

    public function getDescription(): string
    {
        $html = <<<HTML
Обновляет значения количества на складе для товаров на основании актуальных складских остатков их артикулов. Это может потребоваться, если видимый остаток всего товара не соответствует остаткам всех его артикулов
<span class="small task-info"><a href="https://support.webasyst.ru/shop-script/20593/data-repair/#productCounts" target="_blank"><i class="icon16 info"></i> </a></span>
HTML;
        return $html;
    }
}