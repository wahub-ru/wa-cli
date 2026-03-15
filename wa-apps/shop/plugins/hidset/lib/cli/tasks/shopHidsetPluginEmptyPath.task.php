<?php
/*
 * @link https://warslab.ru/
 * @author waResearchLab
 * @Copyright (c) 2021 waResearchLab
 */

class shopHidsetPluginEmptyPathTask extends shopHidsetPluginCli implements shopHidsetPluginCliTaskInterface
{
    public function run($params = null)
    {
        (new shopHidsetPluginRepair())->emptyPathAction();
    }
    public function getCommand(): string
    {
        return 'emptyPath';
    }

    public function getDescription(): string
    {
        $html = <<<HTML
Удаляет лишние пустые поддиректории для пользовательских файлов приложения Shop-Script в директории wa-data/. Лишние пустые директории не используются и только напрасно занимают дисковое пространство сервера служебной информацией
<span class="small task-info"><a href="https://support.webasyst.ru/shop-script/20593/data-repair/#emptyPath" target="_blank"><i class="icon16 info"></i> </a></span>
HTML;
        return $html;
    }
}