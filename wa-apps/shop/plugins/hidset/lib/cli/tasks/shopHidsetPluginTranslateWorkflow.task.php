<?php
/*
 * @link https://warslab.ru/
 * @author waResearchLab
 * @Copyright (c) 2021 waResearchLab
 */

class shopHidsetPluginTranslateWorkflowTask extends shopHidsetPluginCli implements shopHidsetPluginCliTaskInterface
{
    public function run($params = null)
    {
        (new shopHidsetPluginRepair())->translateWorkflowAction();
    }
    public function getCommand(): string
    {
        return 'translateWorkflow';
    }

    public function getDescription(): string
    {
        $html = <<<HTML
Исправляет перевод названий статусов и действий с заказами на текущий язык пользователя
<span class="small task-info"><a href="https://support.webasyst.ru/shop-script/20593/data-repair/#translateWorkflow" target="_blank"><i class="icon16 info"></i> </a></span>
HTML;
        return $html;
    }
}