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

return [
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
  'select_canonical_variable' => array(
    'title' => _wp('Переменная canonical'),
    'description' => _wp('Что делать плагину с переменной canonical на страницах магазина при перезаписи ref="canonical"'),
    'control_type' => waHtmlControl::SELECT,
    'value' => 1,
    'options' => array(
      0 => _wp('ничего не делать'),
      1 => _wp('обнуление (null)'),
      2 => _wp('ставить новое значение canonical'),
    ),
  ),
  'select_canonical_head' => array(
    'title' => _wp('canonical в head'),
    'description' => _wp('Что делать плагину с параметром canonical в $wa->head()'),
    'control_type' => waHtmlControl::SELECT,
    'value' => 3,
    'options' => array(
      0 => _wp('ничего не делать'),
      1 => _wp('обнуление (null)'),
      2 => _wp('перезапись через setCanonical'),
      3 => _wp('перезапись в обход setCanonical (без учета всех проверок setCanonical)'),
    ),
  ),
  'plugin_help' => array(
    'description' => '<a style="color: #03c; font-size: 18px;" href="https://easy-it.ru/seo/webasyst/instruktsiya-k-plaginu-easy-link-canonical/" target="_blank">Инструкция</a>',
    'control_type' => waHtmlControl::HELP,
  ),
  // ----------------------------------------------------------------
  'get_param_help' => array(
    'title' => _wp(''),
    'description' => array(
      _wp('')
    ),
    'value' => _wp('Правила для страниц с GET параметрами'),
    'style' => 'margin: 1em 0 0;font-size: large;font-weight: 600;',
    'control_type' => waHtmlControl::HELP,
    'custom_description_wrapper' => '<span class="hint">%s</span>',
  ),
  'can_process_all_get_param' => array(
    'title' => _wp('link canonical для всех страниц c get параметрами'),
    'description' => array(
      _wp('Проставить link canonical для всех страниц c get параметрами')
    ),
    'value' => 0,
    'class' => 'toggle_home',
    'control_type' => waHtmlControl::CHECKBOX,
    'control_separator' => '<hr>',
  ),
  'can_process_get_param' => array(
    'title' => _wp('Устанавливать canonical для страниц с get параметрами'),
    'description' => array(
      _wp('Правила для страниц с GET параметрами')
    ),
    'value' => 0,
    'class' => 'can_process_get_param',
    'control_type' => waHtmlControl::CHECKBOX,
  ),

  'get_param_table' => array(
    'control_type' => waHtmlControl::CUSTOM,
    'title' => '',
    'description' => '',
    'class' => 'js_can_process_get_param',
    'callback' => [
        'shopEasylinkcanonicalPlugin', 'customTableGetHtml'
    ],
  ),
  // ----------------------------------------------------------------
  'divider_1' => array(
    'title' => _wp(''),
    'description' => array(
      _wp('')
    ),
    'value' => _wp(''),
    'control_type' => waHtmlControl::HELP,
    'custom_description_wrapper' => '<hr>',
  ),

  'get_pages_help' => array(
    'title' => _wp(''),
    'description' => array(
      _wp('')
    ),
    'value' => _wp('Персональные правила для страниц с GET параметрами'),
    'style' => 'margin: 1em 0 0;font-size: large;font-weight: 600;',
    'control_type' => waHtmlControl::HELP,
    'custom_description_wrapper' => '<span class="hint">%s</span>',
  ),
  'can_process_get_pages' => array(
    'title' => _wp('link canonical для всех страниц c get параметрами'),
    'description' => array(
      _wp('Проставить link canonical для всех страниц c get параметрами')
    ),
    'value' => 0,
    'class' => 'can_process_get_pages',
    'control_type' => waHtmlControl::CHECKBOX,
    'control_separator' => '<hr>',
  ),

  'get_pages_table' => array(
    'control_type' => waHtmlControl::CUSTOM,
    'title' => '',
    'description' => '',
    'class' => 'js_can_process_get_pages',
    'callback' => [
        'shopEasylinkcanonicalPlugin', 'customTablePagesHtml'
    ],
  ),

  // ----------------------------------------------------------------

  'divider_2' => array(
    'title' => _wp(''),
    'description' => array(
      _wp('')
    ),
    'value' => _wp(''),
    'control_type' => waHtmlControl::HELP,
    'custom_description_wrapper' => '<hr>',
  ),

  'static_pages_help' => array(
    'title' => _wp(''),
    'description' => array(
      _wp('')
    ),
    'value' => _wp('Правила для статических страниц'),
    'style' => 'margin: 1em 0 0;font-size: large;font-weight: 600;',
    'control_type' => waHtmlControl::HELP,
    'custom_description_wrapper' => '<span class="hint">%s</span>',
  ),
  'can_process_static_pages' => array(
    'title' => _wp('link canonical для статических страниц'),
    'description' => array(
      _wp('Проставить link canonical для статических страниц')
    ),
    'value' => 0,
    'class' => 'can_process_static_pages',
    'control_type' => waHtmlControl::CHECKBOX,
    'control_separator' => '<hr>',
  ),


  'static_pages_table' => array(
    'control_type' => waHtmlControl::CUSTOM,
    'title' => '',
    'description' => '',
    'class' => 'js_can_process_static_pages',
    'callback' => [
        'shopEasylinkcanonicalPlugin', 'customTableStaticPagesHtml'
    ],
  ),
  // ----------------------------------------------------------------

  'divider_3' => array(
    'title' => _wp(''),
    'description' => array(
      _wp('')
    ),
    'value' => _wp(''),
    'control_type' => waHtmlControl::HELP,
    'custom_description_wrapper' => '<hr>',
  ),

  'can_process_product_pages' => array(
    'title' => _wp('link canonical для всех статических  страниц'),
    'description' => array(
      _wp('Проставить link canonical для всех статических  страниц (будут указывать на себя)')
    ),
    'value' => 0,
    'class' => 'toggle_home',
    'control_type' => waHtmlControl::CHECKBOX,
    'control_separator' => '<hr>',
  ),
];