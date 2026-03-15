<?php

/**
 * @author Artem Prichinenko <aprichinenko@yandex.ru>
 */
class blogThumbpagePluginSettingsAction extends waViewAction
{

    public function execute()
    {
        $settings = blogThumbpagePlugin::getPluginSettings();

        $default = array(
            'status' => 0,
        );

        $this->view->assign('settings', array_merge($default, $settings));
    }

}
