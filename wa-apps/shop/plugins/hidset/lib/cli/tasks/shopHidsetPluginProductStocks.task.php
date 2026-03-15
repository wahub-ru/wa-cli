<?php
/*
 * @link https://warslab.ru/
 * @author waResearchLab
 * @Copyright (c) 2021 waResearchLab
 */

class shopHidsetPluginProductStocksTask extends shopHidsetPluginCli implements shopHidsetPluginCliTaskInterface
{
    public function run($params = null)
    {
        (new shopHidsetPluginRepair())->productStocksAction();
    }

    public function getCommand(): string
    {
        return 'productStocks';
    }

    public function getDescription(): string
    {
        $html = <<<HTML
Удаляет лишние записи о складских остатках товаров и артикулов для тех складов, которые уже удалены. Эти лишние записи не используются и могут мешать работе магазина 
<span class="small task-info"><a href="https://support.webasyst.ru/shop-script/20593/data-repair/#productStocks" target="_blank"><i class="icon16 info"></i> </a></span>
HTML;
        return $html;
    }
}