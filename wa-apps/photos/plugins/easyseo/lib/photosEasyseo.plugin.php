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

class photosEasyseoPlugin extends photosPlugin
{
  private $templates = [];

  private $cache_volotile = [];
  private $cache_valid_for = 172800; // 2 days in seconds


  private $data;

  /**
   * load specific settings of this plugin
   *
   * @param string $setting
   * @return string | number
   */
  private function GetSetting($setting)
  {
    return wa('photos')->getPlugin('easyseo')->getSettings($setting);
  }

  /**
   * loads the templates that plagin uses
   *
   * @return array
   */
  private function GetTemplates()
  {
    if ($this->templates) {
      $templates = $this->templates;
    } else {
      $templates = [
        "home" => [
          'is_enabled' => $this->GetSetting('home_toggle'),
          'meta_title' => $this->GetSetting('home_meta_title'),
          'meta_keywords' => "",
          'meta_description' => $this->GetSetting('home_meta_description'),
          'h1' => $this->GetSetting('home_h1'),
        ],
        "album" => [
          'is_enabled' => $this->GetSetting('album_toggle'),
          'pagination' => $this->GetSetting('album_pagination'),
          'meta_title' => $this->GetSetting('album_meta_title'),
          'meta_title_forced' => $this->GetSetting('album_meta_title_forced'),
          'meta_keywords' => "",
          'meta_description' => $this->GetSetting('album_meta_description'),
          'meta_description_forced' => $this->GetSetting('album_meta_description_forced'),
          'h1' => $this->GetSetting('album_h1'),
          'h1_forced' => $this->GetSetting('album_h1_forced'),
        ],
        "image" => [
          'is_enabled' => $this->GetSetting('image_toggle'),
          'pagination' => $this->GetSetting('image_pagination'),
          'meta_title' => $this->GetSetting('image_meta_title'),
          'meta_title_forced' => $this->GetSetting('image_meta_title_forced'),
          'meta_keywords' => "",
          'meta_description' => $this->GetSetting('image_meta_description'),
          'meta_description_forced' => $this->GetSetting('image_meta_description_forced'),
          'h1' => $this->GetSetting('image_h1'),
        ],
      ];

      $this->templates = $templates;
    }
    return $templates;
  }

  // ----------------------------------------------------------------
  /**
   * check if plugin has been enabled in settings
   * @return int
   */
  private function isEnabled()
  {
    return $this->GetSetting('plugin_enabled');
  }

  /**
   * check if module has been enabled in settings
   * @return int
   */
  private function isModuleEnabled($type = "home")
  {
    return $this->GetSetting($type."_toggle");
  }

  /**
   * hook to frontend_layout event/handler
   * @return void
   */
  public function frontendHomepage()
  {
    if (!$this->isEnabled() && !$this->isModuleEnabled("home"))
    return;

    // page integration
    $view_vars = wa()->getView()->getVars();
    if(!isset($view_vars['page'])){
      return;
    }

    $this->insertDataToPage("home");
  }

  /**
   * hook to frontend_collection event/handler
   * @return void
   */
  public function frontendCollection(&$album)
  {
    if (!$this->isEnabled() && !$this->isModuleEnabled("album"))
      return;
    // $album['album_id'] = $album['id'];
    $album['page'] = waRequest::get('page', 1) ?? "";
    $this->insertDataToPage("album", $album);
  }

  /**
   * hook to frontend_photo event/handler
   * @return void
   */
  public function frontendPhoto(&$image)
  {
    if (!$this->isEnabled() && !$this->isModuleEnabled("image"))
      return;
    $image['page'] = waRequest::get('page', 1) ?? "";
    $this->insertDataToPage("image", $image);
  }

  // ----------------------------------------------------------------
  /**
   * check if $metadata contains any of $beware_of_array elements as key
   * @param array<string> $beware_of_array
   * @param array $metadata
   * @return bool
   */
  function CanApply($beware_of_array, $metadata)
  {

    $can_apply = true;
    if ($beware_of_array) {
      foreach ($beware_of_array as $beware_of_element) {
        if ($beware_of_element && isset($metadata[$beware_of_element])) {
          $can_apply = false;
        }
      }
    }

    return $can_apply;
  }

  /**
   * override meta info and smarty variables in page
   * @param string $page_type
   * @param array $metadata
   * @return void
   */
  public function insertDataToPage($page_type = "home", $metadata = [])
  {
    $this->loadData($page_type, $metadata);

    if($this->data){
      // a and not (not b and c) = when we have meta_title and it is forced or not forced and we didnt get value already
      // warning: wa()->getResponse()->getTitle() is never empty, check for title in $metadata by key meta_title
      if ($this->data['meta_title'] && !(!(array_key_exists('meta_title_forced', $this->data) && $this->data['meta_title_forced']) && array_key_exists('meta_title', (array) $metadata) && $metadata['meta_title'])) {
        if ($this->CanApply(explode(",", (array_key_exists('beware_of', $this->data) ? $this->data['beware_of'] : '')), wa()->getView()->getVars())) {
          wa()->getResponse()->setTitle($this->data['meta_title']);
        }
      }

      if ($this->data['meta_keywords'] && !(!$this->data['meta_keywords_forced'] && wa()->getResponse()->getMeta('keywords'))) {
        if ($this->CanApply(explode(",", (array_key_exists('beware_of', $this->data) ? $this->data['beware_of'] : '')), wa()->getView()->getVars())) {
          wa()->getResponse()->setMeta('keywords', $this->data['meta_keywords']);
        }
      }
      if ($this->data['meta_description'] && !(!(array_key_exists('meta_description_forced', $this->data) && $this->data['meta_description_forced']) && wa()->getResponse()->getMeta('description'))) {
        if ($this->CanApply(explode(",", (array_key_exists('beware_of', $this->data) ? $this->data['beware_of'] : '')), wa()->getView()->getVars())) {
          wa()->getResponse()->setMeta('description', $this->data['meta_description']);
        }
      }

      if ($this->data['h1'] && !(!@$this->data['h1_forced'] && wa()->getView()->getVars('h1'))) {
        wa()->getView()->assign('h1', $this->data['h1']);
        wa()->getView()->assign('h1_forced', @$this->data['h1_forced']);
      }

      if (array_key_exists('description', $this->data) && $this->data['description'] && !(!$this->data['description_forced'] && wa()->getView()->getVars('home_page_description'))) {
        wa()->getView()->assign('home_page_description', $this->data['description']);
      }
    }
  }

  // ------------------------------------------------------------------------
  /**
   * load filled templates or create ones
   * @param string $page_type
   * @param array $metadata
   * @return void
   */
  private function loadData($type, $metadata = [])
  {

    $cache_key = $type . '_' . (isset($metadata['id']) ? $metadata['id'] : '');
    $cached_data = $this->loadCache($type);

    $data = [];
    if (is_null($cached_data)) {
      $data = $this->GetTemplates()[$type];
      $data = $this->renderAll($data, $metadata);
      $this->setCache($cache_key, $data);
    } else {
      $data = $cached_data;
    }

    $this->data = $data;
  }

  /**
   * render all templates via render engine using $metadata values
   * @param array $templates
   * @param array $metadata
   * @return mixed
   */
  public function renderAll($templates, &$metadata = [])
  {
    if(!$templates){
      return;
    }
    $can_process = $templates['is_enabled'];
    // pagination prerender
    if (waRequest::get('page', 0, 'int') && isset($templates['pagination'])) { // chech if we have at least pagination enabled on the page
      $pagination_template = $templates['pagination'];
      $pagination = $this->render($pagination_template, $metadata);
      $metadata['pagination'] = $pagination;
    }
    else {
      $metadata['pagination'] = '';
    }

    if ($can_process) {
      foreach ($templates as $template_key => $template) {
        $templates[$template_key] = $this->render($template, $metadata);
      }
    } else {
      $templates = [];
    }

    return $templates;
  }

  /**
   * render specific templat via render engine using $metadata values
   * @param array $templates
   * @param array $metadata
   * @return mixed
   */
  public function render($template, $metadata = [])
  {
    if (strpos($template, '{') === false) {
      return $template;
    }

    try {
      $view = new waSmarty3View(wa(), array('compile_id' => 'easyseo', ));

      $smarty_modifiers_dir = wa()->getAppPath('plugins/easyseo/lib/smarty-modifiers', 'photos');
      $view->smarty->addPluginsDir($smarty_modifiers_dir);
      $view->smarty->caching = false;

      $this->hydrateSmartyView($view);

      $page_num = waRequest::get('page', 0, 'int');
      $view->assign(array('page_number' => ""));
      if ($page_num) {
        $view->assign(array('page_number' => $page_num));
      }
      if ($metadata && array_key_exists('page', (array) $metadata) && $metadata['page']) {
        $view->assign(array('page_number' => $metadata['page']));
      }

      $view->assign(array('pagination' => ''));
      if ($metadata && array_key_exists('pagination', (array) $metadata) && $metadata['pagination']) {
        $view->assign(array('pagination' => $page_num > 1 ? $metadata['pagination'] : ''));
      }

      $result = $view->fetch('string:' . $template);

      if (strlen($result) === 0) {
        return " ";
      }

      return $result;
    } catch (SmartyCompilerException $e) {
      return '[error] ' . $template;
    }
  }

  /**
   * inject every known page variables into smarty engine object
   * @param waSmarty3View $smarty_view
   * @return waSmarty3View
   */
  public function hydrateSmartyView($smarty_view)
  {
    $smarty_view->assign(wa()->getView()->getVars());

    $config = wa('shop')->getConfig();
    $vars['store_info'] = $config->getGeneralSettings();
    $vars['host'] = waRequest::server('HTTP_HOST');

    $vars['storefront'] = array('name' => "storefront_name", 'fields' => [], );

    $smarty_view->assign($vars);

    return $smarty_view;
  }


  // --------------------------------------------------------------------
  // cache
  /**
   * cache the following data:
   * values ​​of variables that are then used in rendering (for the time while rendering is in progress, because the data often changes)
   * values ​​of filled templates in terms of page types and ids of the corresponding items (for the time of ttl or until the cache is cleared)
   */


  /**
   * get in-memory cache
   * @param string $key
   * @return mixed
   */
  private function getVolotileCache($key)
  {
    $ret = null;
    if (key_exists($key, $this->cache_volotile)) {
      $ret = $this->cache_volotile[$key];
    }
    return $ret;
  }

  /**
   * set in-memory cache
   * @param string $key
   * @param mixed $value
   * @return mixed
   */
  private function setVolotileCache($key, $value)
  {
    $this->cache_volotile[$key] = $value;
    return $value;
  }

  /**
   * cleanup in-memory cache
   * @return void
   */
  private function cleanVolotileCache()
  {
    $this->cache_volotile = [];
  }

  /**
   * save cache
   * @param string $cache_key
   * @param mixed $value
   * @return mixed
   */
  private function setCache($cache_key, $value)
  {
    /**
     * 1. get settings to get user permission to cache at all
     * 2. if false then do nothing
     * 3. if true
     * 4. create obj waSerializeCache with string 'easyseo/templates/$cache_key'
     * 4. save data
     * return data
     */
    if ($this->GetSetting('has_cache')) {
      $cache = $this->getCachePointer($cache_key);
      $cache->set($value);
    }
    return $value;
  }

  /**
   * load cache
   * @param string $cache_key
   * @return mixed
   */
  private function loadCache($cache_key)
  {
    /**
     * 1. get settings to get user permission to cache at all
     * 2. if false then return null (becouse cache is empty)
     * 3. if true
     * 4. create obj waSerializeCache with string 'easyseo/templates/$cache_key'
     * 4. load data
     * return whether cache data is
     */
    $ret = null;
    if ($this->GetSetting('has_cache')) {
      $cache = $this->getCachePointer($cache_key);
      if ($cache->isCached()) {
        $ret = $cache->get();
      }
    }
    return $ret;
  }

  /**
   * cache object factory
   * @param string $cache_key
   * @return waSerializeCache
   */
  function getCachePointer($cache_key)
  {
    return new waSerializeCache('easyseo/templates/' . $cache_key, $this->cache_valid_for * (0.01 * rand(-20, 20) + 1), 'photos');
  }

}