<?php
/*
 * @link https://warslab.ru/
 * @author waResearchLab
 * @Copyright (c) 2021 waResearchLab
 */

class shopHidsetPluginThumbTask extends shopHidsetPluginCli implements shopHidsetPluginCliTaskInterface
{
    public function run($params = null)
    {
        (new shopHidsetPluginRepair())->thumbAction();
    }
    public function getCommand(): string
    {
        return 'thumb';
    }

    public function getDescription(): string
    {
        $html = <<<HTML
Восстанавливает потерянные файлы в директории wa-data/, необходимые для автоматического формирования эскизов изображений товаров и промокарточек
<span class="small task-info"><a href="https://support.webasyst.ru/shop-script/20593/data-repair/#thumb" target="_blank"><i class="icon16 info"></i> </a></span>
HTML;
        return $html;
    }
}