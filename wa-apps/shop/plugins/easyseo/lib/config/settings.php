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
    'description' => '<a style="color: #03c; font-size: 18px;" href="https://easy-it.ru/seo/instruktsiya-k-plaginu-easy-seo/" target="_blank">Инструкция</a>',
    'control_type' => waHtmlControl::HELP,
  ),

  // ----------------------------------------------------------------


  'hint_title' => array(
    'title' => _wp(''),
    'description' => array(
      _wp('')
    ),
    'value' => _wp('Переменные'),
    'style' => shopEasyseoViewHelper::getSettingsHeaderStyle(),
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
    'description' => shopEasyseoViewHelper::getSettingsHint1(),
    'control_type' => waHtmlControl::HELP,
    'class' => 'hint_toggle',
  ),

  'hint2' => array(
    'description' => shopEasyseoViewHelper::getSettingsHint2(),
    'control_type' => waHtmlControl::HELP,
    'class' => 'hint_toggle',
  ),

  'hint3' => array(
    'description' => shopEasyseoViewHelper::getSettingsHint3(),
    'control_type' => waHtmlControl::HELP,
    'class' => 'hint_toggle',
  ),

  // ----------------------------------------------------------------

  'home_help' => array(
    'title' => _wp(''),
    'description' => array(
      _wp('')
    ),
    'value' => _wp('Главная страница'),
    'style' => shopEasyseoViewHelper::getSettingsHeaderStyle(),
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
    'style' => shopEasyseoViewHelper::getSettingsTextareaStyle(),
    'control_type' => waHtmlControl::TEXTAREA,
    'cols' => '100',
    'class' => 'home_toggle',
  ),
  'home_meta_description' => array(
    'title' => _wp('Meta description'),
    'description' => array(
      _wp('')
    ),
    'value' => '',
    'style' => shopEasyseoViewHelper::getSettingsTextareaStyle(),
    'control_type' => waHtmlControl::TEXTAREA,
    'cols' => '100',
    'class' => 'home_toggle',
  ),
  'home_h1' => array(
    'title' => _wp('h1'),
    'description' => array(
      _wp('')
    ),
    'value' => '',
    'style' => shopEasyseoViewHelper::getSettingsTextareaH1Style(),
    'control_type' => waHtmlControl::TEXTAREA,
    'cols' => '100',
    'class' => 'home_toggle',
  ),

  // ----------------------------------------------------------------
  'category_help' => array(
    'title' => _wp(''),
    'description' => array(
      _wp('')
    ),
    'value' => _wp('Категории'),
    'style' => shopEasyseoViewHelper::getSettingsHeaderStyle(),
    'control_type' => waHtmlControl::HELP,
    'custom_description_wrapper' => '<span class="hint">%s</span>',
  ),
  'category_toggle' => array(
    'title' => _wp(''),
    'description' => array(
      _wp('свернуть (выключить) / развернуть')
    ),
    'value' => 0,
    'class' => 'toggle_category',
    'control_type' => waHtmlControl::CHECKBOX,
  ),

  'category_pagination' => array(
    'title' => _wp('{$pagination}'),
    'description' => array(
      _wp('переменная для подставления, если на странице нет пагинации то выведет пустую строку')
    ),
    'value' => '- {$page_number}',
    'style' => shopEasyseoViewHelper::getSettingsTextareaH1Style(),
    'control_type' => waHtmlControl::TEXTAREA,
    'cols' => '100',
    'class' => 'category_toggle',
  ),

  'category_meta_title' => array(
    'title' => _wp('Meta title'),
    'description' => array(
      _wp('')
    ),
    'value' => '{$category.name} купить в интернет-магазине {$store_info.name}',
    'style' => shopEasyseoViewHelper::getSettingsTextareaStyle(),
    'control_type' => waHtmlControl::TEXTAREA,
    'class' => 'category_toggle',
    'cols' => '100',
  ),
  'category_meta_title_forced' => array(
    'title' => _wp('Приоритет'),
    'description' => _wp('Всегда заменять значение (иначе заменять только если исходное значение пустое)'),
    'control_type' => waHtmlControl::CHECKBOX,
    'value' => 1,
    'class' => 'category_toggle',
  ),
  'category_meta_description' => array(
    'title' => _wp('Meta description'),
    'description' => array(
      _wp('')
    ),
    'value' => '{$store_info.name} представляет к покупке {$category.name} по цене от {$category.min_price}. Гарантия качества⭐️⭐️⭐️⭐️⭐️',
    'style' => shopEasyseoViewHelper::getSettingsTextareaStyle(),
    'control_type' => waHtmlControl::TEXTAREA,
    'cols' => '100',
    'class' => 'category_toggle',
  ),
  'category_meta_description_forced' => array(
    'title' => _wp('Приоритет'),
    'description' => _wp('Всегда заменять значение (иначе заменять только если исходное значение пустое)'),
    'control_type' => waHtmlControl::CHECKBOX,
    'value' => 1,
    'class' => 'category_toggle',
  ),

  'category_h1' => array(
    'title' => _wp('h1'),
    'description' => array(
      _wp('')
    ),
    'value' => '',
    'style' => shopEasyseoViewHelper::getSettingsTextareaH1Style(),
    'control_type' => waHtmlControl::TEXTAREA,
    'cols' => '100',
    'class' => 'category_toggle',
  ),
  'category_h1_forced' => array(
    'title' => _wp('Приоритет'),
    'description' => _wp('Всегда заменять значение (иначе заменять только если исходное значение пустое)'),
    'control_type' => waHtmlControl::CHECKBOX,
    'value' => 1,
    'class' => 'category_toggle',
  ),
  // ----------------------------------------------------------------
  'product_help' => array(
    'title' => _wp(''),
    'description' => array(
      _wp('')
    ),
    'value' => _wp('Товары'),
    'style' => shopEasyseoViewHelper::getSettingsHeaderStyle(),
    'control_type' => waHtmlControl::HELP,
    'custom_description_wrapper' => '<span class="hint">%s</span>',
  ),
  'product_toggle' => array(
    'title' => _wp(''),
    'description' => array(
      _wp('свернуть (выключить) / развернуть')
    ),
    'value' => 0,
    'class' => 'toggle_product',
    'control_type' => waHtmlControl::CHECKBOX,
  ),

  'product_pagination' => array(
    'title' => _wp('{$pagination}'),
    'description' => array(
      _wp('переменная для подставления, если на странице нет пагинации то выведет пустую строку')
    ),
    'value' => '- {$page_number}',
    'style' => shopEasyseoViewHelper::getSettingsTextareaH1Style(),
    'control_type' => waHtmlControl::TEXTAREA,
    'cols' => '100',
    'class' => 'product_toggle',
  ),

  'product_meta_title' => array(
    'title' => _wp('Meta title'),
    'description' => array(
      _wp('')
    ),
    'value' => '{$product.name|ucfirst} купить в интернет-магазине {$store_info.name}',
    'style' => shopEasyseoViewHelper::getSettingsTextareaStyle(),
    'control_type' => waHtmlControl::TEXTAREA,
    'cols' => '100',
    'class' => 'product_toggle',
  ),
  'product_meta_title_forced' => array(
    'title' => _wp('Приоритет'),
    'description' => _wp('Всегда заменять значение (иначе заменять только если исходное значение пустое)'),
    'control_type' => waHtmlControl::CHECKBOX,
    'value' => 1,
    'class' => 'product_toggle',
  ),
  'product_meta_description' => array(
    'title' => _wp('Meta description'),
    'description' => array(
      _wp('')
    ),
    'value' => '{$product.name|ucfirst} по цене {$product.format_price}. Гарантия качества⭐️⭐️⭐️⭐️⭐️',
    'style' => shopEasyseoViewHelper::getSettingsTextareaStyle(),
    'control_type' => waHtmlControl::TEXTAREA,
    'cols' => '100',
    'class' => 'product_toggle',
  ),
  'product_meta_description_forced' => array(
    'title' => _wp('Приоритет'),
    'description' => _wp('Всегда заменять значение (иначе заменять только если исходное значение пустое)'),
    'control_type' => waHtmlControl::CHECKBOX,
    'value' => 1,
    'class' => 'product_toggle',
  ),
  'product_h1' => array(
    'title' => _wp('h1'),
    'description' => array(
      _wp('')
    ),
    'value' => '',
    'style' => shopEasyseoViewHelper::getSettingsTextareaH1Style(),
    'control_type' => waHtmlControl::TEXTAREA,
    'cols' => '100',
    'class' => 'product_toggle',
  ),
  'product_h1_forced' => array(
    'title' => _wp('Приоритет'),
    'description' => _wp('Всегда заменять значение (иначе заменять только если исходное значение пустое)'),
    'control_type' => waHtmlControl::CHECKBOX,
    'value' => 1,
    'class' => 'product_toggle',
  ),

  // ----------------------------------------------------------------
  'brands_help' => array(
    'title' => _wp(''),
    'description' => array(
      _wp('интеграция с плагинами для работы с брендами идет на основе переменных шаблона brand и brands (в частности проверено на плагине productbrands)')
    ),
    'value' => _wp('Бренды'),
    'style' => shopEasyseoViewHelper::getSettingsHeaderStyle(),
    'control_type' => waHtmlControl::HELP,
    'custom_description_wrapper' => '<span class="hint">%s</span>',
  ),
  'brand_toggle' => array(
    'title' => _wp(''),
    'description' => array(
      _wp('свернуть (выключить) / развернуть')
    ),
    'value' => 0,
    'class' => 'toggle_brand',
    'control_type' => waHtmlControl::CHECKBOX,
  ),

  'brands_meta_title' => array(
    'title' => _wp('Meta title'),
    'description' => array(
      _wp('')
    ),
    'value' => '',
    'style' => shopEasyseoViewHelper::getSettingsTextareaStyle(),
    'control_type' => waHtmlControl::TEXTAREA,
    'cols' => '100',
    'class' => 'brand_toggle',
  ),
  'brands_meta_title_forced' => array(
    'title' => _wp('Приоритет'),
    'description' => _wp('Всегда заменять значение (иначе заменять только если исходное значение пустое)'),
    'control_type' => waHtmlControl::CHECKBOX,
    'value' => 1,
    'class' => 'brand_toggle',
  ),
  'brands_meta_description' => array(
    'title' => _wp('Meta description'),
    'description' => array(
      _wp('')
    ),
    'value' => '',
    'style' => shopEasyseoViewHelper::getSettingsTextareaStyle(),
    'control_type' => waHtmlControl::TEXTAREA,
    'cols' => '100',
    'class' => 'brand_toggle',
  ),
  'brands_meta_description_forced' => array(
    'title' => _wp('Приоритет'),
    'description' => _wp('Всегда заменять значение (иначе заменять только если исходное значение пустое)'),
    'control_type' => waHtmlControl::CHECKBOX,
    'value' => 1,
    'class' => 'brand_toggle',
  ),

  'brands_h1' => array(
    'title' => _wp('h1'),
    'description' => array(
      _wp('')
    ),
    'value' => '',
    'style' => shopEasyseoViewHelper::getSettingsTextareaH1Style(),
    'control_type' => waHtmlControl::TEXTAREA,
    'cols' => '100',
    'class' => 'brand_toggle',
  ),
  'brands_h1_forced' => array(
    'title' => _wp('Приоритет'),
    'description' => _wp('Всегда заменять значение (иначе заменять только если исходное значение пустое)'),
    'control_type' => waHtmlControl::CHECKBOX,
    'value' => 1,
    'class' => 'brand_toggle',
  ),

  // ----------------------------------------------------------------

  'brand_help' => array(
    'title' => _wp(''),
    'description' => array(
      _wp('')
    ),
    'value' => _wp('Страница определенного бренда'),
    'style' => shopEasyseoViewHelper::getSettingsHeaderStyle(),
    'control_type' => waHtmlControl::HELP,
    'class' => 'brand_toggle',
    'custom_description_wrapper' => '<span class="hint">%s</span>',
  ),

  'brand_pagination' => array(
    'title' => _wp('{$pagination}'),
    'description' => array(
      _wp('переменная для подставления, если на странице нет пагинации то выведет пустую строку')
    ),
    'value' => '- {$page_number}',
    'style' => shopEasyseoViewHelper::getSettingsTextareaH1Style(),
    'control_type' => waHtmlControl::TEXTAREA,
    'cols' => '100',
    'class' => 'brand_toggle',
  ),

  'brand_meta_title' => array(
    'title' => _wp('Meta title'),
    'description' => array(
      _wp('')
    ),
    'value' => '',
    'style' => shopEasyseoViewHelper::getSettingsTextareaStyle(),
    'control_type' => waHtmlControl::TEXTAREA,
    'cols' => '100',
    'class' => 'brand_toggle',
  ),
  'brand_meta_title_forced' => array(
    'title' => _wp('Приоритет'),
    'description' => _wp('Всегда заменять значение (иначе заменять только если исходное значение пустое)'),
    'control_type' => waHtmlControl::CHECKBOX,
    'value' => 1,
    'class' => 'brand_toggle',
  ),
  'brand_meta_description' => array(
    'title' => _wp('Meta description'),
    'description' => array(
      _wp('')
    ),
    'value' => '',
    'style' => shopEasyseoViewHelper::getSettingsTextareaStyle(),
    'control_type' => waHtmlControl::TEXTAREA,
    'cols' => '100',
    'class' => 'brand_toggle',
  ),
  'brand_meta_description_forced' => array(
    'title' => _wp('Приоритет'),
    'description' => _wp('Всегда заменять значение (иначе заменять только если исходное значение пустое)'),
    'control_type' => waHtmlControl::CHECKBOX,
    'value' => 1,
    'class' => 'brand_toggle',
  ),

  'brand_h1' => array(
    'title' => _wp('h1'),
    'description' => array(
      _wp('')
    ),
    'value' => '',
    'style' => shopEasyseoViewHelper::getSettingsTextareaH1Style(),
    'control_type' => waHtmlControl::TEXTAREA,
    'cols' => '100',
    'class' => 'brand_toggle',
  ),
  'brand_h1_forced' => array(
    'title' => _wp('Приоритет'),
    'description' => _wp('Всегда заменять значение (иначе заменять только если исходное значение пустое)'),
    'control_type' => waHtmlControl::CHECKBOX,
    'value' => 1,
    'class' => 'brand_toggle',
  ),
  // ----------------------------------------------------------------
  'meta_help' => array(
    'title' => _wp(''),
    'description' => array(
      _wp('')
    ),
    'value' => _wp('Мета настройки'),
    'style' => shopEasyseoViewHelper::getSettingsHeaderStyle(),
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