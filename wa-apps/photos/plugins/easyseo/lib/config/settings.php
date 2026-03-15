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
  'plugin_enabled' => array(
    'title' => _wp('Включить/выключить плагин'),
    'description' => _wp(''),
    'control_type' => waHtmlControl::SELECT,
    'value' => 1,
    'options' => array(
      0 => _wp('Отключить'),
      1 => _wp('Включить'),
    ),
  ),
  'plugin_help' => array(
    'description' => '<a style="color: #03c; font-size: 18px;" href="https://easy-it.ru/seo/webasyst/instruktsiya-k-plaginu-easy-seo/" target="_blank">Инструкция</a>',
    'control_type' => waHtmlControl::HELP,
  ),

  // ----------------------------------------------------------------


  'hint_title' => array(
    'title' => _wp(''),
    'description' => array(
      _wp('')
    ),
    'value' => _wp('Переменные'),
    'style' => photosEasyseoViewHelper::getSettingsHeaderStyle(),
    'control_type' => waHtmlControl::HELP,
    'custom_description_wrapper' => '<span class="hint">%s</span>',
  ),

  'hint_toggle' => array(
    'title' => _wp(''),
    'description' => array(
      _wp('свернуть / развернуть блок с подсказсками')
    ),
    'value' => 0,
    'class' => 'toggle_hint',
    'control_type' => waHtmlControl::CHECKBOX,
  ),
  'hint' => array(
    'description' => photosEasyseoViewHelper::getSettingsHint1(),
    'control_type' => waHtmlControl::HELP,
    'class' => 'hint_toggle',
  ),

  'hint2' => array(
    'description' => photosEasyseoViewHelper::getSettingsHint2(),
    'control_type' => waHtmlControl::HELP,
    'class' => 'hint_toggle',
  ),

  'hint3' => array(
    'description' => photosEasyseoViewHelper::getSettingsHint3(),
    'control_type' => waHtmlControl::HELP,
    'class' => 'hint_toggle',
  ),

  // ----------------------------------------------------------------

  'home_help' => array(
    'title' => _wp(''),
    'description' => array(
      _wp('')
    ),
    'value' => _wp('Страницы'),
    'style' => photosEasyseoViewHelper::getSettingsHeaderStyle(),
    'control_type' => waHtmlControl::HELP,
    'custom_description_wrapper' => '<span class="hint">%s</span>',
  ),
  'home_toggle' => array(
    'title' => _wp(''),
    'description' => array(
      _wp('свернуть (выключить) / развернуть')
    ),
    'value' => 0,
    'class' => 'toggle_home',
    'control_type' => waHtmlControl::CHECKBOX,
  ),

  'home_meta_title' => array(
    'title' => _wp('Meta title'),
    'description' => array(
      _wp('')
    ),
    'value' => '',
    'style' => photosEasyseoViewHelper::getSettingsTextareaStyle(),
    'control_type' => waHtmlControl::TEXTAREA,
    'cols' => '100',
    'class' => 'home_toggle',
  ),
    'home_meta_title_forced' => array(
    'title' => _wp('Приоритет'),
    'description' => _wp('Всегда заменять значение (иначе заменять только если исходное значение пустое)'),
    'control_type' => waHtmlControl::CHECKBOX,
    'value' => 1,
    'class' => 'home_toggle',
  ),
  'home_meta_description' => array(
    'title' => _wp('Meta description'),
    'description' => array(
      _wp('')
    ),
    'value' => '',
    'style' => photosEasyseoViewHelper::getSettingsTextareaStyle(),
    'control_type' => waHtmlControl::TEXTAREA,
    'cols' => '100',
    'class' => 'home_toggle',
  ),
    'home_meta_description_forced' => array(
    'title' => _wp('Приоритет'),
    'description' => _wp('Всегда заменять значение (иначе заменять только если исходное значение пустое)'),
    'control_type' => waHtmlControl::CHECKBOX,
    'value' => 1,
    'class' => 'home_toggle',
  ),
  'home_h1' => array(
    'title' => _wp('h1'),
    'description' => array(
      _wp('')
    ),
    'value' => '',
    'style' => photosEasyseoViewHelper::getSettingsTextareaH1Style(),
    'control_type' => waHtmlControl::TEXTAREA,
    'cols' => '100',
    'class' => 'home_toggle',
  ),
    'home_h1_forced' => array(
    'title' => _wp('Приоритет'),
    'description' => _wp('Всегда заменять значение (иначе заменять только если исходное значение пустое)'),
    'control_type' => waHtmlControl::CHECKBOX,
    'value' => 1,
    'class' => 'home_toggle',
  ),

  // ----------------------------------------------------------------
  'album_help' => array(
    'title' => _wp(''),
    'description' => array(
      _wp('')
    ),
    'value' => _wp('Альбомы'),
    'style' => photosEasyseoViewHelper::getSettingsHeaderStyle(),
    'control_type' => waHtmlControl::HELP,
    'custom_description_wrapper' => '<span class="hint">%s</span>',
  ),
  'album_toggle' => array(
    'title' => _wp(''),
    'description' => array(
      _wp('свернуть (выключить) / развернуть')
    ),
    'value' => 0,
    'class' => 'toggle_album',
    'control_type' => waHtmlControl::CHECKBOX,
  ),

  'album_pagination' => array(
    'title' => _wp('{$pagination}'),
    'description' => array(
      _wp('переменная для подставления, если на странице нет пагинации то выведет пустую строку')
    ),
    'value' => '- {$page_number}',
    'style' => photosEasyseoViewHelper::getSettingsTextareaH1Style(),
    'control_type' => waHtmlControl::TEXTAREA,
    'cols' => '100',
    'class' => 'album_toggle',
  ),

  'album_meta_title' => array(
    'title' => _wp('Meta title'),
    'description' => array(
      _wp('')
    ),
    'value' => '{$album.name} купить в интернет-магазине {$store_info.name}',
    'style' => photosEasyseoViewHelper::getSettingsTextareaStyle(),
    'control_type' => waHtmlControl::TEXTAREA,
    'class' => 'album_toggle',
    'cols' => '100',
  ),
  'album_meta_title_forced' => array(
    'title' => _wp('Приоритет'),
    'description' => _wp('Всегда заменять значение (иначе заменять только если исходное значение пустое)'),
    'control_type' => waHtmlControl::CHECKBOX,
    'value' => 1,
    'class' => 'album_toggle',
  ),
  'album_meta_description' => array(
    'title' => _wp('Meta description'),
    'description' => array(
      _wp('')
    ),
    'value' => '{$store_info.name} представляет к покупке {$album.name} по цене от {$album.min_price}. Гарантия качества⭐️⭐️⭐️⭐️⭐️',
    'style' => photosEasyseoViewHelper::getSettingsTextareaStyle(),
    'control_type' => waHtmlControl::TEXTAREA,
    'cols' => '100',
    'class' => 'album_toggle',
  ),
  'album_meta_description_forced' => array(
    'title' => _wp('Приоритет'),
    'description' => _wp('Всегда заменять значение (иначе заменять только если исходное значение пустое)'),
    'control_type' => waHtmlControl::CHECKBOX,
    'value' => 1,
    'class' => 'album_toggle',
  ),

  'album_h1' => array(
    'title' => _wp('h1'),
    'description' => array(
      _wp('')
    ),
    'value' => '',
    'style' => photosEasyseoViewHelper::getSettingsTextareaH1Style(),
    'control_type' => waHtmlControl::TEXTAREA,
    'cols' => '100',
    'class' => 'album_toggle',
  ),
  'album_h1_forced' => array(
    'title' => _wp('Приоритет'),
    'description' => _wp('Всегда заменять значение (иначе заменять только если исходное значение пустое)'),
    'control_type' => waHtmlControl::CHECKBOX,
    'value' => 1,
    'class' => 'album_toggle',
  ),
  // ----------------------------------------------------------------
  'image_help' => array(
    'title' => _wp(''),
    'description' => array(
      _wp('')
    ),
    'value' => _wp('Фотографии'),
    'style' => photosEasyseoViewHelper::getSettingsHeaderStyle(),
    'control_type' => waHtmlControl::HELP,
    'custom_description_wrapper' => '<span class="hint">%s</span>',
  ),
  'image_toggle' => array(
    'title' => _wp(''),
    'description' => array(
      _wp('свернуть (выключить) / развернуть')
    ),
    'value' => 0,
    'class' => 'toggle_image',
    'control_type' => waHtmlControl::CHECKBOX,
  ),

  'image_pagination' => array(
    'title' => _wp('{$pagination}'),
    'description' => array(
      _wp('переменная для подставления, если на странице нет пагинации то выведет пустую строку')
    ),
    'value' => '- {$page_number}',
    'style' => photosEasyseoViewHelper::getSettingsTextareaH1Style(),
    'control_type' => waHtmlControl::TEXTAREA,
    'cols' => '100',
    'class' => 'image_toggle',
  ),

  'image_meta_title' => array(
    'title' => _wp('Meta title'),
    'description' => array(
      _wp('')
    ),
    'value' => '{$image.name|ucfirst} купить в интернет-магазине {$store_info.name}',
    'style' => photosEasyseoViewHelper::getSettingsTextareaStyle(),
    'control_type' => waHtmlControl::TEXTAREA,
    'cols' => '100',
    'class' => 'image_toggle',
  ),
  'image_meta_title_forced' => array(
    'title' => _wp('Приоритет'),
    'description' => _wp('Всегда заменять значение (иначе заменять только если исходное значение пустое)'),
    'control_type' => waHtmlControl::CHECKBOX,
    'value' => 1,
    'class' => 'image_toggle',
  ),
  'image_meta_description' => array(
    'title' => _wp('Meta description'),
    'description' => array(
      _wp('')
    ),
    'value' => '{$image.name|ucfirst} Гарантия качества⭐️⭐️⭐️⭐️⭐️',
    'style' => photosEasyseoViewHelper::getSettingsTextareaStyle(),
    'control_type' => waHtmlControl::TEXTAREA,
    'cols' => '100',
    'class' => 'image_toggle',
  ),
  'image_meta_description_forced' => array(
    'title' => _wp('Приоритет'),
    'description' => _wp('Всегда заменять значение (иначе заменять только если исходное значение пустое)'),
    'control_type' => waHtmlControl::CHECKBOX,
    'value' => 1,
    'class' => 'image_toggle',
  ),
  'image_h1' => array(
    'title' => _wp('h1'),
    'description' => array(
      _wp('')
    ),
    'value' => '',
    'style' => photosEasyseoViewHelper::getSettingsTextareaH1Style(),
    'control_type' => waHtmlControl::TEXTAREA,
    'cols' => '100',
    'class' => 'image_toggle',
  ),
  'image_h1_forced' => array(
    'title' => _wp('Приоритет'),
    'description' => _wp('Всегда заменять значение (иначе заменять только если исходное значение пустое)'),
    'control_type' => waHtmlControl::CHECKBOX,
    'value' => 1,
    'class' => 'image_toggle',
  ),

  // ----------------------------------------------------------------

  'meta_help' => array(
    'title' => _wp(''),
    'description' => array(
      _wp('')
    ),
    'value' => _wp('Мета настройки'),
    'style' => photosEasyseoViewHelper::getSettingsHeaderStyle(),
    'control_type' => waHtmlControl::HELP,
    'custom_description_wrapper' => '<span class="hint">%s</span>',
  ),
  'has_cache' => array(
    'title' => _wp('Кешировать заполненные шаблоны'),
    'description' => array(
      _wp('Если кеш заполняется слишком быстро то отключите')
    ),
    'checked' => true,
    'value' => 1,
    'control_type' => waHtmlControl::CHECKBOX,
  ),

);