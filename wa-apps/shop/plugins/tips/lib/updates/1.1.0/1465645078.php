<?php
/**
 * Update file for Tips plugin
 *
 * 1.0.0 -> 1.1.0
 *
 * Removes unused files according to Webasyst requirements:
 *  - README.md
 *
 * @author Serge Rodovnichenko <serge@syrnik.com>
 * @version 1.1.0
 * @copyright Serge Rodovnichenko, 2015-2016
 * @license MIT
 */

waFiles::delete(wa('shop')->getConfig()->getPluginPath('tips') . '/README.md');
