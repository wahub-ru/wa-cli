<?php

/**
 * Created by PhpStorm.
 * User: dimka
 * Date: 21.07.17
 * Time: 2:13
 */
class shopBnpcommentsPluginSettingsAction extends waViewAction
{

    public function execute() {

        $plugin = wa()->getPlugin('bnpcomments');
        
        $settings = $plugin->getSettings();
        $this->view->assign('settings', $settings);

        $state_checkbox = waHtmlControl::getControl(waHtmlControl::CHECKBOX, 'shop_bnpcomments[state]', array(
            'value' => 1,
            'checked' => isset($settings['state']) && $settings['state'] ? true : false,
        ));

        $this->view->assign('state_checkbox', $state_checkbox);
    }

}