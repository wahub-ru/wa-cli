<?php
/**
 * Settings Form controller
 *
 * @package Syrattach/controller
 * @author Serge Rodovnichenko <serge@syrnik.com>
 * @version 2.0
 * @copyright (c) 2015, Serge Rodovnichenko
 * @license http://www.webasyst.com/terms/#eula Webasyst
 */

/**
 * Class shopSyrattachPluginSettingsAction
 */
class shopSyrattachPluginSettingsAction extends waViewAction
{
    /**
     * @throws waException
     */
    public function execute()
    {
        if (!$this->getUser()->getRights('shop', 'settings')) {
            throw new waException(_w('Access denied'));
        }

        $plugin = wa('shop')->getPlugin('syrattach');
        $this->getResponse()->setTitle(_wp('Attached Files Plugin Settings'));
        $this->view->assign('settings_controls', $plugin->getControls(array(
            'id'                  => 'syrattach',
            'namespace'           => 'shop_syrattach',
            'title_wrapper'       => '%s',
            'description_wrapper' => '<br><span class="hint">%s</span>',
            'control_wrapper'     => '<div class="name">%s</div><div class="value">%s %s</div>'
        )));
    }
}
