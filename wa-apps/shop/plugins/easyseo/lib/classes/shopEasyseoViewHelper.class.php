<?php

/*
 *
 * Easyseo plugin for Webasyst framework, created for Shopscript app.
 *
 * @name Easyseo
 * @author EasyIT LLC
 * @link https://easy-it.ru/
 * @copyright Copyright (c) 2017, EasyIT LLC
 * @version 1.0.0, 2024-10-01
 *
 */

/**
 * helper to assist in different situations
 */
class shopEasyseoViewHelper extends waPluginViewHelper
{
  /**
   * get settings header style
   * @return string
   */
  static public function getSettingsHeaderStyle()
  {
    return 'margin: 1em 0 0;font-size: large;font-weight: 600;';
  }
  /**
   * get settings textarea style
   * @return string
   */
  static public function getSettingsTextareaStyle()
  {
    return 'min-width:600px;height:100px;';
  }
  /**
   * get settings textarea h1 style
   * @return string
   */
  static public function getSettingsTextareaH1Style()
  {
    return 'min-width:600px;height:50px;';
  }
  /**
   * load hint to settings from file
   * @return string html
   */
  static public function getSettingsHint1()
  {
    return wa()->getView()->fetch(wa()->getAppPath('plugins/easyseo/templates/actions/backend/hint1.html'));
  }
  /**
   * load hint to settings from file
   * @return string html
   */
  static public function getSettingsHint2()
  {
    return wa()->getView()->fetch(wa()->getAppPath('plugins/easyseo/templates/actions/backend/hint2.html'));
  }
  /**
   * load hint to settings from file
   * @return string html
   */
  static public function getSettingsHint3()
  {
    return wa()->getView()->fetch(wa()->getAppPath('plugins/easyseo/templates/actions/backend/hint3.html'));
  }
}