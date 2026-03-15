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

class shopEasylinkcanonicalPlugin extends shopPlugin
{

  private static function get_array_key_last($array)
  {
    if (!is_array($array) || empty($array)) {
      return NULL;
    }
    return array_keys($array)[count($array) - 1];
  }

  private static function is_str_starts_with($haystack, $needle)
  {
    $strlen_needle = mb_strlen($needle);
    if (mb_substr($haystack, 0, $strlen_needle) == $needle) {
      return true;
    }
    return false;
  }

  public function frontendHead()
  {
    if (!waConfig::get('is_template') && $this->getSettings('plugin_enabled') == 1 && !wa()->getView()->getVars('error_code')) {
      self::UpdateCanonicals();
    }
  }

  private function UpdateCanonicals()
  {
    $canonical = null;
    $action = wa()->getView()->getVars('action');

    $potential_canonical = self::SelectCanonicalByAnyGetParam();
    if ($potential_canonical) {
      $canonical = $potential_canonical;
    }

    if (in_array($action, ['product', 'productReviews', 'productPage'])) {
      $potential_canonical = self::SelectCanonicalOfShop();
      if ($potential_canonical) {
        $canonical = $potential_canonical;
      }
    }

    if ($action == 'page') {
      $potential_canonical = self::SelectCanonicalOfStaticPage();
      if ($potential_canonical) {
        $canonical = $potential_canonical;
      }
    }

    $potential_canonical = self::SelectCanonicalByGet();
    if ($potential_canonical) {
      $canonical = $potential_canonical;
    }

    $potential_canonical = self::SelectCanonicalByUrl();
    if ($potential_canonical) {
      $canonical = $potential_canonical;
    }

    $potential_canonical = self::SelectCanonicalCustom();
    if ($potential_canonical) {
      $canonical = $potential_canonical;
    }

    // -------------------------------
    $this->OverrideCanonical($canonical);
  }

  // ----------------------------------------------------------------

  private function SelectCanonicalOfShop()
  {
    $settings = $this->GetCurrentShopfrontSettings();
    $ret = null;

    if ($settings['can_process_product_pages']) {
      $ret = self::GetCurAddressWithoutParams();
    }

    return $ret;
  }


  private function SelectCanonicalOfStaticPage()
  {
    $settings = $this->GetCurrentShopfrontSettings();
    $ret = null;

    if ($settings['can_process_static_pages']) {
      $ret = self::GetCurAddressWithoutParams();
    }

    return $ret;
  }

  /**
   * set canonical sa host if request has any get param
   * @return string|null
   */
  private function SelectCanonicalByAnyGetParam()
  {
    $ret = null;
    $settings = $this->GetCurrentShopfrontSettings();
    if ($settings['can_process_all_get_param']) {
      if (self::GetParamList()) {
        $ret = self::GetCurAddressWithoutParams();
      }
    }

    return $ret;
  }
  // #################################
  private function SelectCanonicalByGet()
  {
    $settings = $this->GetCurrentShopfrontSettings();
    $ret = null;
    if ($settings['can_process_get_param']) {
      $link_list = self::GetTableData('get');

      $get_list = self::GetParamList();
      //
      foreach ($link_list as $link_key => $target_value) {
        $link_keys = explode(',', $link_key); // process comma separated list
        $can_update = true;
        $tmp_target = null;
        foreach ($link_keys as $link_to_check) {
          $expected_get_param = [];
          parse_str($link_to_check, $expected_get_param); // get param parcing
          $is_incorporated = self::DeepArrayIntersectionCheck($get_list, $expected_get_param); // check if target list is inside list of get params
          if($is_incorporated){
            $tmp_target = $target_value;
          }
          else {
            $can_update = false;
          }
        }
        if($tmp_target && $can_update){
          $ret = $tmp_target;
        }
      }

      switch ($ret) {
        case 'self':
          $ret = self::GetCurAddress();
          break;
        case 'clear':
          $ret = self::GetCurAddressWithoutParams();
          break;

        default:
          break;
      }
    }


    return $ret;
  }

  function DeepArrayIntersectionCheck(&$reference, &$target) {
    $ret = true;
    // depth of check = 2
    // if endpoint target value is '' then we accept any value from reference

    if(is_array($target)){
      foreach ($target as $target_key_lvl1 => $target_value_lvl1) {
        //
        if(isset($reference[$target_key_lvl1])){
          if(is_array($target_value_lvl1)){
            //
            foreach ($target_value_lvl1 as $target_key_lvl2 => $target_value_lvl2) {
              if(isset($reference[$target_key_lvl1][$target_key_lvl2])){
                if(is_array($target_value_lvl2)){
                  //
                  foreach ($target_value_lvl2 as $target_key_lvl3 => $target_value_lvl3) {
                    if(isset($reference[$target_key_lvl1][$target_key_lvl2][$target_key_lvl3])){
                      if($target_value_lvl3 !== '' && $reference[$target_key_lvl1][$target_key_lvl2][$target_key_lvl3] !== $target_value_lvl3){
                        $ret = false;
                      }
                    }
                    else {
                      $ret = false;
                    }
                  }
                }
                else {
                  if($target_value_lvl2 !== '' && $reference[$target_key_lvl1][$target_key_lvl2] !== $target_value_lvl2){
                    $ret = false;
                  }
                }
              }
              else {
                $ret = false;
              }
            }
          }
          else {
            if($target_value_lvl1 !== '' && $reference[$target_key_lvl1] !== $target_value_lvl1){
              $ret = false;
            }
          }
        }
        else {
          $ret = false;
        }
      }
    }

    return $ret;
  }

  private function SelectCanonicalByUrl()
  {
    $settings = $this->GetCurrentShopfrontSettings();
    $ret = null;
    if ($settings['can_process_get_pages']) {
      $link_list = self::GetTableData('get_pages');

      foreach ($link_list as $link_key => $link_value) {
        if ($link_key == self::GetCurAddress() || $link_key == wa()->getConfig()->getRequestUrl(false, true)) {
          $ret = $link_value;
        }
      }

      switch ($ret) {
        case 'self':
          $ret = self::GetCurAddress();
          break;
        case 'clear':
          $ret = self::GetCurAddressWithoutParams();
          break;

        default:
          break;
      }
    }

    return $ret;
  }

  private function SelectCanonicalCustom()
  {
    $settings = $this->GetCurrentShopfrontSettings();
    $ret = null;
    if ($settings['can_process_static_pages']) {
      $link_list = self::GetTableData('static_pages');

      foreach ($link_list as $link_key => $link_value) {
        if ($link_key == self::GetCurAddress() || $link_key == wa()->getConfig()->getRequestUrl(false, true)) {
          $ret = $link_value;
        }
      }
    }

    return $ret;
  }

  // ----------------------------------------------------------------

  /**
   * override canonical in current view
   * @param string|null $canonical link that then is used in template to show original page path
   * @return void
   */
  private function OverrideCanonical($canonical)
  {
    if ($canonical) {
      $settings = $this->GetCurrentShopfrontSettings();
      $select_canonical_variable = $settings['select_canonical_variable'];
      switch ($select_canonical_variable) {
        case 0:
          break;
        case 1:
          wa()->getView()->assign('canonical', null);
          break;
        case 2:
          wa()->getView()->assign('canonical', $canonical); // override for older versions
          break;
        default:
          break;
      }
      $select_canonical_head = $settings['select_canonical_head'];
      switch ($select_canonical_head) {
        case 0:
          break;
        case 1:
          wa()->getResponse()->setCanonical(null);
          break;
        case 2:
          wa()->getResponse()->setCanonical($canonical);
          break;
        case 3:
          wa()->getResponse()->setCanonical(null); // unset calculated canonical by core of shop-script (if we can)
          // cant override to be new canonical url as actual url
          wa()->getResponse()->addHeader('Link', "<{$canonical}>; rel='canonical'");
          wa()->getResponse()->setMeta('canonical', (string) $canonical);
          break;
        default:
          break;
      }
    }
  }

  /**
   * get server host from requst
   * @return string
   */
  private static function getHost()
  {
    // TODO: cache it
    waRequest::isHttps() ? $http = 'https://' : $http = 'http://';
    return $http . waRequest::server('HTTP_HOST');
  }

  /**
   * load current address without get params.
   * @return string
   */
  private static function GetCurAddressWithoutParams()
  {
    return self::getHost() . wa()->getConfig()->getRequestUrl(false, true);
  }

  /**
   * Get current address.
   * @return string
   */
  private static function GetCurAddress()
  {
    return self::getHost() . wa()->getConfig()->getCurrentUrl();
  }

  /**
   * list of get params from the request
   * @return array returns array of get params or empty array
   */
  private static function GetParamList()
  {
    $get_params = waRequest::get();
    return gettype($get_params) == 'array' ? $get_params : [];
  }

  private function GetCurrentShopfrontSettings()
  {
    // TODO: load settings from slices of storefronts
    return $this->getSettings();
  }

  private static function GetAllSettings()
  {
    return wa('shop')->getPlugin('easylinkcanonical')->getSettings();
  }

  private static function GetSettingsByPrefix($prefix, $exception = [])
  {
    $settings = self::GetAllSettings();
    $ret = [];

    foreach ($settings as $settings_key => $settings_value) {
      if (self::is_str_starts_with($settings_key, $prefix)) {
        $extracted_key = str_replace($prefix, '', $settings_key);
        if (!in_array($extracted_key, $exception)) {
          $ret[$extracted_key] = $settings_value;
        }
      }
    }
    ksort($ret);
    return $ret;
  }


  private static function DeleteSettingsRowsByPrefix($prefix, $suffixes, $target = '', $exception = ['template'])
  {
    $settings_list = self::GetSettingsByPrefix($prefix);
    $settings_model = self::getSettingsModel();

    foreach ($suffixes as $suffix_key => $suffix_value) {
      foreach ($settings_list as $settings_key => $settings_value) {
        // delete rows from settings if row prefix is empty (collumns to delete defined by suffixes)
        if ($settings_value == $target && !in_array($settings_key, $exception)) {
          $setting_to_delete = $suffix_value . $settings_key;
          //
          $settings_model->del('shop.easylinkcanonical', $setting_to_delete);
        }
      }
    }

  }

  public static function customTableGetHtml($name = "", $params = [])
  {
    $classes = isset($params['class']) ? $params['class'] : [];
    return self::tableInputHtml('get', ['table_classes' => $classes]);
  }
  public static function customTablePagesHtml($name = "", $params = [])
  {
    $classes = isset($params['class']) ? $params['class'] : [];
    return self::tableInputHtml('get_pages', [
      'table_classes' => $classes,
      'text' => ['title' => 'Укажите страницу с get-параметром', 'placeholder' => 'url страницы с get-параметром']
    ]);
  }
  public static function customTableStaticPagesHtml($name = "", $params = [])
  {
    $classes = isset($params['class']) ? $params['class'] : [];
    return self::tableInputHtml('static_pages', [
      'table_classes' => $classes,
      'use_selector' => false,
      'text' => ['title' => 'Укажите URL страницы', 'placeholder' => 'url страницы '],
      'custom_text' => ['title' => 'Укажите link canonical', 'placeholder' => 'url канонической страницы']
    ]);
  }

  public static function tableInputHtml($prefix = 'get', $field_params = [])
  {
    $suffixes = [$prefix . '_input_value_', $prefix . '_selector_canonical_', $prefix . '_input_canonical_custom_'];
    self::DeleteSettingsRowsByPrefix($prefix . '_input_value_', $suffixes);
    //

    $plugin_id = 'easylinkcanonical';
    $path = wa()->getAppPath() . "/plugins/{$plugin_id}";

    $view = wa()->getView();
    //
    $dropdown_input_templates = self::GetDropdowns($prefix, $field_params);
    $text_inputs_templates = self::GetTextInputs($prefix, $field_params);
    $custom_text_inputs_templates = self::GetCustomTextInputs($prefix, $field_params);
    $last_row_index = self::GetLastRowNumber($prefix);
    //
    
    $view->assign("table_classes", isset($field_params['table_classes']) ? implode(' ', $field_params['table_classes']) : '');
    //
    $view->assign("text_inputs_templates", $text_inputs_templates);
    $view->assign("custom_text_inputs_templates", $custom_text_inputs_templates);
    $view->assign("dropdown_input_templates", $dropdown_input_templates);
    $view->assign("last_row_index", $last_row_index);
    $view->assign("prefix", $prefix);
    //
    $out = $view->fetch($path . '/templates/table.input.html');
    return $out;
  }

  private static function rotateMatrix90($matrix)
  {
    $matrix = array_values($matrix);
    $matrix90 = array();

    foreach (array_keys($matrix[0]) as $column) {
      $matrix90[] = array_reverse(array_column($matrix, $column));
    }

    return $matrix90;
  }

  private static function GetTableData($prefix = 'get', $coll_indexes = ['input' => '_input_value_', 'selector' => '_selector_canonical_', 'custom' => '_input_canonical_custom_'])
  {
    $ret = [];
    $colls = [];

    $row_count = self::GetRowCount($prefix);
    foreach ($coll_indexes as $k => $index) {
      $selector = $prefix . $index;
      $coll = self::GetSettingsByPrefix($selector, ['template']);
      $colls[$k] = self::ArrayPadding($coll, $row_count);
    }

    $table_rows = self::rotateMatrix90($colls);

    foreach ($table_rows as $table_row) {

      if (count($table_row) == 3) {
        $row_custom = $table_row[0];
        $row_selector = $table_row[1];
        $row_input = $table_row[2];
      } else {
        $row_custom = $table_row[0];
        $row_selector = 3;
        $row_input = $table_row[1];
      }


      if ($row_input) {
        switch ($row_selector) {
          case 1:
            $ret[$row_input] = 'self';
            break;
          case 2:
            $ret[$row_input] = 'clear';
            break;
          case 3:
          default:
            $ret[$row_input] = $row_custom;
            break;
        }
      }
    }

    return $ret;
  }

  private static function GetRowCount($prefix = 'get', $input_suffix = '_input_value_')
  {
    $input_prefix = $prefix . $input_suffix;
    return count(self::GetSettingsByPrefix($input_prefix, ['template']));
  }

  private static function GetLastRowNumber($prefix = 'get', $input_suffix = '_input_value_')
  {
    $input_prefix = $prefix . $input_suffix;
    return self::get_array_key_last(self::GetSettingsByPrefix($input_prefix, ['template']));
  }

  private static function GetDropdowns($prefix = 'get', $field_params = [])
  {
    $default = 1;

    if (isset($field_params['use_selector']) && $field_params['use_selector'] == false) {
      return [];
    }

    $input_prefix = $prefix . '_selector_canonical_';
    $settings = self::GetSettingsByPrefix($input_prefix);

    $settings = ['template' => $default] + $settings;

    $row_count = self::GetRowCount($prefix) + 1;
    $settings = self::ArrayPadding($settings, $row_count, $default);

    $ret = [];

    $control_params = array(
      'namespace' => 'shop_easylinkcanonical',
      'control_wrapper' => '<div class="field"><div class="name">%s</div><div class="value">%s%s</div></div>',
      'title_wrapper' => '%s',
      'description_wrapper' => '<br><span class="hint">%s</span>',
    );

    foreach ($settings as $index => $value) {
      $ret[] = waHtmlControl::getControl(waHtmlControl::SELECT, $input_prefix . $index, array(
        'options' => array(
          1 => 'указывать на себя',
          2 => 'указывать на адрес без параметров',
          3 => 'пользовательское значение',
        ),
        'value' => (int) $value,
        'class' => 'js_onchange_dropdown',
        'title' => 'Действие с link canonical',
        'description' => 'выберите значение из списка',
      ) + $control_params);
    }

    return $ret;
  }

  private static function GetTextInputs($prefix = 'get', $field_params = [])
  {

    $title = 'Укажите get-параметр	';
    if (isset($field_params['text']['title'])) {
      $title = $field_params['text']['title'];
    }
    $placeholder = 'page=2, раge, price_max, color...';
    if (isset($field_params['text']['placeholder'])) {
      $placeholder = $field_params['text']['placeholder'];
    }

    $input_prefix = $prefix . '_input_value_';
    $settings = self::GetSettingsByPrefix($input_prefix);

    $settings = ['template' => ''] + $settings;

    $row_count = self::GetRowCount($prefix) + 1;
    $settings = self::ArrayPadding($settings, $row_count);

    $ret = [];

    $control_params = array(
      'namespace' => 'shop_easylinkcanonical',
      'control_wrapper' => '<div class="field"><div class="name">%s</div><div class="value">%s%s</div></div>',
      'title_wrapper' => '%s',
      'description_wrapper' => '<br><span class="hint">%s</span>'
    );

    foreach ($settings as $index => $value) {
      $ret[] = waHtmlControl::getControl(waHtmlControl::INPUT, $input_prefix . $index, array(
        'value' => $value,
        'title' => $title,
        'placeholder' => $placeholder,
        'class' => 'long',
      ) + $control_params);
    }

    return $ret;
  }

  private static function GetCustomTextInputs($prefix = 'get', $field_params = [])
  {

    $field_can_be_disabled = true;
    // field_params processing
    if (isset($field_params['use_selector']) && $field_params['use_selector'] == false) {
      $field_can_be_disabled = false;
    }

    $title = 'Укажите значение';
    if (isset($field_params['custom_text']['title'])) {
      $title = $field_params['custom_text']['title'];
    }
    $placeholder = 'Укажите значение';
    if (isset($field_params['custom_text']['placeholder'])) {
      $placeholder = $field_params['custom_text']['placeholder'];
    }
    //

    $input_prefix = $prefix . '_input_canonical_custom_';
    $settings = self::GetSettingsByPrefix($input_prefix);

    $settings = ['template' => ''] + $settings;

    $row_count = self::GetRowCount($prefix) + 1;
    $settings = self::ArrayPadding($settings, $row_count);


    $ret = [];
    //
    $control_params = array(
      'namespace' => 'shop_easylinkcanonical',
      'control_wrapper' => '<div class="field"><div class="name">%s</div><div class="value">%s%s</div></div>',
      'title_wrapper' => '%s',
      'description_wrapper' => '<br><span class="hint">%s</span>'
    );

    $class = 'long js_custom_on_dropdown';
    if ($field_can_be_disabled) {
      $class .= ' disabled';
    }

    foreach ($settings as $index => $value) {
      $ret[] = waHtmlControl::getControl(waHtmlControl::INPUT, $input_prefix . $index, array(
        'value' => $value,
        'title' => $title,
        'placeholder' => $placeholder,
        'class' => $class,
      ) + $control_params);
    }

    //
    return $ret;
  }

  /**
   * fill or cut array to the specific length
   * @param array $arr
   * @param int $lenght
   * @param mixed $default_value
   */
  public static function ArrayPadding($arr, $lenght, $default_value = '')
  {

    $ret = $arr;
    if (count($arr) > $lenght && $lenght > 0) {
      $chunked_array = array_chunk($arr, $lenght);
      $ret = reset($chunked_array);
    }

    if (count($arr) < $lenght) {
      $ret = array_replace($arr, array_fill(count($arr), $lenght - count($arr), $default_value));
    }

    return $ret;
  }
}