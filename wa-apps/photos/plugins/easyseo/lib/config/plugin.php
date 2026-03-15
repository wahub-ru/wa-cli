<?php

/*
 *
 * Easyseo plugin for Webasyst framework, created for Photos app.
 *
 * @name Easyseo
 * @author EasyIT LLC
 * @link https://easy-it.ru/
 * @copyright Copyright (c) 2017, EasyIT LLC
 * @version 1.0.0, 2025-10-01
 *
 */

return array(
  'name' => /*_wp*/('Easy SEO (photos)'),
  'description' => /*_wp*/('Easy SEO'),
  'vendor' => '851416',
  'version' => '1.0.0',
  'img' => 'img/icon.svg',
  'icon' => array(
    16 => 'img/icon.svg'
  ),
  'custom_settings' => true,
  'handlers' => array(
    'frontend_collection' => 'frontendCollection',
    'frontend_photo' => 'frontendPhoto',

    'frontend_layout' => 'frontendHomepage',

  ),
);
//EOF