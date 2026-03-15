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

return array(
  'name' => /*_wp*/('Easy SEO'),
  'description' => /*_wp*/('Easy SEO'),
  'vendor' => '851416',
  'version' => '1.0.2',
  'img' => 'img/icon.svg',
  'icon' => array(
    16 => 'img/icon.svg'
  ),
  'custom_settings' => true,
  'handlers' => array(
    'frontend_homepage' => 'frontendHomepage',
    'frontend_category' => 'frontendCategory',
    'frontend_product' => 'frontendProduct',

    'frontend_head' => 'frontendHead',

    'frontend_search' => 'frontendSearch',
  ),
);
//EOF