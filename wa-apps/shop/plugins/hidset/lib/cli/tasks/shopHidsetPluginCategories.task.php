<?php
/*
 * @link https://warslab.ru/
 * @author waResearchLab
 * @Copyright (c) 2021 waResearchLab
 */

class shopHidsetPluginCategoriesTask extends shopHidsetPluginCli implements shopHidsetPluginCliTaskInterface
{
    public function run($params = null)
    {
        (new shopHidsetPluginRepair())->categoriesAction();
    }

    public function getCommand(): string
    {
        return 'categories';
    }

    public function getDescription(): string
    {
        $html = <<<HTML
Исправляет информацию о вложенности категорий товаров. Ошибки в информации о вложенности категорий могут проявляться в виде неработающего дерева категорий в секции «Товары»
<span class="small task-info"><a href="https://support.webasyst.ru/shop-script/20593/data-repair/#categories" target="_blank"><i class="icon16 info"></i> </a></span>
HTML;
        return $html;
    }
}