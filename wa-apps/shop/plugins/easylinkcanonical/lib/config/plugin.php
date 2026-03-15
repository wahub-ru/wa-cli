<?php

/*
 *
 * Easylinkcanonical plugin for Webasyst framework, created for Shopscript app.
 *
 * @name Easylinkcanonical
 * @author EasyIT LLC
 * @link https://easy-it.ru/
 * @copyright Copyright (c) 2017, EasyIT LLC
 * @version 1.0.0, 2025-03-25
 *
 */

return array(
  'name' => /*_wp*/ ('Easy Link canonical'),
  'description' => /*_wp*/ ('Добавление Link canonical на страницы'),
  'vendor' => '851416',
  'version' => '1.0.0',
  'img' => 'img/icon.svg',
  'icon' => array(
    16 => 'img/icon.svg'
  ),
  'custom_settings' => true,
  'handlers' => array(
    'frontend_head' => 'frontendHead',
  ),
);
//EOF