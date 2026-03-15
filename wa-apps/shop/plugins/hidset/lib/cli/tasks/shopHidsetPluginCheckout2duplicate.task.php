<?php
/*
 * @link https://warslab.ru/
 * @author waResearchLab
 * @Copyright (c) 2021 waResearchLab
 */

class shopHidsetPluginCheckout2duplicateTask extends shopHidsetPluginCli implements shopHidsetPluginCliTaskInterface
{
    public function run($params = null)
    {
        (new shopHidsetPluginRepair())->checkout2duplicateAction();
    }

    public function getCommand(): string
    {
        return 'checkout2duplicate';
    }

    public function getDescription(): string
    {
        $html = <<<HTML
Исправляет ошибки в настройках витрин, использующих режим оформления заказа в корзине
<span class="small task-info"><a href="https://support.webasyst.ru/shop-script/20593/data-repair/#checkout2duplicate" target="_blank"><i class="icon16 info"></i> </a></span>
HTML;
        return $html;
    }
}