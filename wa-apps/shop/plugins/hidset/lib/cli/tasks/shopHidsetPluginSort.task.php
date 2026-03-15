<?php
/*
 * @link https://warslab.ru/
 * @author waResearchLab
 * @Copyright (c) 2021 waResearchLab
 */

class shopHidsetPluginSortTask extends shopHidsetPluginCli implements shopHidsetPluginCliTaskInterface
{
    public function run($params = null)
    {
        (new shopHidsetPluginRepair())->sortAction();
    }

    public function getCommand(): string
    {
        return 'sort';
    }

    public function getDescription(): string
    {
        $html = <<<HTML
Исправляет неработающую сортировку разных элементов
<span class="small task-info"><a href="https://support.webasyst.ru/shop-script/20593/data-repair/#sort" target="_blank"><i class="icon16 info"></i> </a></span>
HTML;
        return $html;
    }
}