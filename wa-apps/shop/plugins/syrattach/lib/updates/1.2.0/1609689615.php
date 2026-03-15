<?php
/**
 * @author Serge Rodovnichenko <serge@syrnik.com>
 * @copyright Serge Rodovnichenko, 2021
 * @license Webasyst
 */
try {
    $plugin_path = wa('shop')->getConfig()->getPluginPath('syrattach');
    waFiles::delete($plugin_path . '/templates/Attachments');
} catch (Exception $e) {
}

