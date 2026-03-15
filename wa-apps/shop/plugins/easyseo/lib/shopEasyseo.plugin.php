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

class shopEasyseoPlugin extends shopPlugin
{
  private $cachedCategory;
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
    return wa('shop')->getPlugin('easyseo')->getSettings($setting);
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
        "category" => [
          'is_enabled' => $this->GetSetting('category_toggle'),
          'pagination' => $this->GetSetting('category_pagination'),
          'meta_title' => $this->GetSetting('category_meta_title'),
          'meta_title_forced' => $this->GetSetting('category_meta_title_forced'),
          'meta_keywords' => "",
          'meta_description' => $this->GetSetting('category_meta_description'),
          'meta_description_forced' => $this->GetSetting('category_meta_description_forced'),
          'h1' => $this->GetSetting('category_h1'),
          'h1_forced' => $this->GetSetting('category_h1_forced'),
        ],
        "product" => [
          'is_enabled' => $this->GetSetting('product_toggle'),
          'pagination' => $this->GetSetting('product_pagination'),
          'meta_title' => $this->GetSetting('product_meta_title'),
          'meta_title_forced' => $this->GetSetting('product_meta_title_forced'),
          'meta_keywords' => "",
          'meta_description' => $this->GetSetting('product_meta_description'),
          'meta_description_forced' => $this->GetSetting('product_meta_description_forced'),
          'h1' => $this->GetSetting('product_h1'),
        ],
        "brands" => [
          'is_enabled' => $this->GetSetting('brand_toggle'),
          'meta_title' => $this->GetSetting('brands_meta_title'),
          'meta_title_forced' => $this->GetSetting('brands_meta_title_forced'),
          'meta_keywords' => "",
          'meta_description' => $this->GetSetting('brands_meta_description'),
          'meta_description_forced' => $this->GetSetting('brands_meta_description_forced'),
          'h1' => $this->GetSetting('brands_h1'),
          'h1_forced' => $this->GetSetting('brands_h1_forced'),
          'beware_of' => 'product,category',
        ],
        "brand" => [
          'is_enabled' => $this->GetSetting('brand_toggle'),
          'pagination' => $this->GetSetting('brand_pagination'),
          'meta_title' => $this->GetSetting('brand_meta_title'),
          'meta_title_forced' => $this->GetSetting('brand_meta_title_forced'),
          'meta_keywords' => "",
          'meta_description' => $this->GetSetting('brand_meta_description'),
          'meta_description_forced' => $this->GetSetting('brand_meta_description_forced'),
          'h1' => $this->GetSetting('brand_h1'),
          'h1_forced' => $this->GetSetting('brand_h1_forced'),
          'h1_forced_brand' => $this->GetSetting('brand_h1_forced'),
        ]
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
   * hook to frontend_homepage event/handler
   * @return void
   */
  public function frontendHomepage()
  {
    if (!$this->isEnabled() && !$this->isModuleEnabled("home"))
    return;

    $this->insertDataToPage("home");
  }

  /**
   * hook to frontend_category event/handler
   * @return void
   */
  public function frontendCategory(&$category)
  {
    if (!$this->isEnabled() && !$this->isModuleEnabled("category"))
      return;

    $category['category_id'] = $category['id'];
    $category['page'] = waRequest::get('page', 1) ?? "";
    $this->insertDataToPage("category", $category);
  }

  /**
   * hook to frontend_product event/handler
   * @return void
   */
  public function frontendProduct(&$product)
  {
    if (!$this->isEnabled() && !$this->isModuleEnabled("product"))
      return;

    $product['product_id'] = $product['id'];
    $this->insertDataToPage("product", $product);
  }

  // ----------------------------------------------------------------

  /**
   * hook to frontend_head event/handler (aka every page hook) (injected into every header)
   * only use is to integrate with brands related plugin
   * @return void
   */
  public function frontendHead()
  {
    if (!$this->isEnabled() && !$this->isModuleEnabled("brand"))
      return;

    $vars = wa()->getView()->getVars();


    // plugin integration (every plugin that addes brand/brands variables)
    if (isset($vars['brand'])) {
      $this->insertDataToPage("brand", ['brand' => $vars['brand']]);
    }
    if (isset($vars['brands'])) {
      $this->insertDataToPage("brands", ['brands' => $vars['brands']]);
    }

  }


  function frontendSearch()
  {
    // brand plugin integration
    if ($this->isEnabled()) {
      if ($this->IntegrationPluginBrand()) {
        $current_route = wa()->getRouting()->getCurrentUrl();
        $path_blocks = explode('/', $current_route);
        if (reset(($path_blocks)) === 'brand') {
          $brand_object = ['name' => $path_blocks[1]];
          $this->insertDataToPage("brand", ['brand' => $brand_object]);
        }
      }
    }
  }

  function IntegrationPluginBrand()
  {
    $ret = false;
    $plugins = wa()->getConfig()->getPlugins();
    if (isset($plugins['brands'])) {
      $ret = true;
    }
    return $ret;
  }

  // ----------------------------------------------------------------
  /**
   * check if $metadata contains any of $beware_of_array elements as key
   * @param array[string] $beware_of_array
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

      if ($this->data['h1'] && !(!$this->data['h1_forced'] && wa()->getView()->getVars('h1'))) {
        wa()->getView()->assign('h1', $this->data['h1']);
        wa()->getView()->assign('h1_forced', $this->data['h1_forced']);
        if (array_key_exists('h1_forced_brand', $this->data)) {
          wa()->getView()->assign('h1_forced_brand', $this->data['h1_forced_brand']);
        }

        if ($this->data['h1_forced'] && $this->IntegrationPluginBrand()) { // integration
          wa()->getView()->assign('title', $this->data['h1']);
        }
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
    $this->cleanVolotileCache();
  }

  /**
   * render all templates via render engine using $metadata values
   * @param array $templates
   * @param array $metadata
   * @return mixed
   */
  public function renderAll($templates, &$metadata = [])
  {
    $can_process = $templates['is_enabled'];
    // pagination prerender
    if (waRequest::get('page', 0, 'int') && isset($templates['pagination'])) { // chech if we have at least pagination enabled on the page
      $pagination_template = $templates['pagination'];
      $pagination = $this->render($pagination_template, $metadata);
      $metadata['pagination'] = $pagination;
    }
    else {
      $metadata['pagination'] = 'AAA';
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

      $smarty_modifiers_dir = wa()->getAppPath('plugins/easyseo/lib/smarty-modifiers', 'shop');
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

      if ($metadata && array_key_exists('category_id', (array) $metadata) && $metadata['category_id']) {
        $this->hydrateCategorySmartyView($metadata['category_id'], $view);
      }
      if ($metadata && array_key_exists('product_id', (array) $metadata) && $metadata['product_id']) {
        $this->hydrateProductSmartyView($metadata['product_id'], $view);
      }
      if ($metadata && array_key_exists('brands', (array) $metadata) && $metadata['brands']) {
        $this->hydrateBrandsSmartyView($metadata['brands'], $view);
      }
      if ($metadata && array_key_exists('brand', (array) $metadata) && $metadata['brand']) {
        $this->hydrateBrandSmartyView($metadata['brand'], $view);
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
    $vars['store_info'] = array(
      'phone' => $config->getGeneralSettings('phone'),
      'name' => $config->getGeneralSettings('name'),
    );
    $vars['host'] = waRequest::server('HTTP_HOST');

    $vars['storefront'] = array('name' => "storefront_name", 'fields' => [], );

    $smarty_view->assign($vars);

    return $smarty_view;
  }

  /**
   * add category data to smarty engine object
   * @param int $category_id
   * @param waSmarty3View $smarty_view
   * @return void
   */
  public function hydrateCategorySmartyView($category_id, $smarty_view)
  {
    $category = $this->getCategoryData($category_id);

    $vars = array();
    $vars['category'] = $this->extendCategory($category, true);
    $vars['parent_categories'] = $vars['category']['parents'];
    $vars['root_category'] = reset($vars['category']['parents']);
    $vars['parent_category'] = end($vars['category']['parents']);
    if(!$vars['parent_category']){
      $vars['parent_category'] = ['name'=> ''];
    };
    $vars['parent_categories_names'] = array();

    foreach ($vars['category']['parents'] as $parent) {
      $vars['parent_categories_names'][] = $parent['name'];
    }

    $vars['categories_names'] = $vars['parent_categories_names'];
    // assign collected values
    $smarty_view->assign($vars);
  }

  /**
   * add product data to smarty engine object
   * @param int $product_id
   * @param waSmarty3View $smarty_view
   * @return void
   */
  public function hydrateProductSmartyView($product_id, $smarty_view)
  {
    $vars = [];
    $product = $this->getProductData($product_id);
    $vars['product'] = $this->extendProduct($product);

    $vars['name'] = $vars['product']['name'];
    $vars['summary'] = $vars['product']['summary'];
    $vars['price'] = shop_currency_html($vars['product']['price'], null, null, true);

    $smarty_view->assign($vars);
  }

  /**
   * add brands data to smarty engine object
   * @param array $brands
   * @param waSmarty3View $smarty_view
   * @return void
   */
  public function hydrateBrandsSmartyView($brands, $smarty_view)
  {
    $vars = [];
    $brands_names = [];

    foreach ($brands as $brand_id => $brand) {
      $brands_names[] = $brand['name'];
    }

    $vars['brand_names'] = $brands_names;

    $smarty_view->assign($vars);
  }

  public function hydrateBrandSmartyView($brand, $smarty_view)
  {
    $vars = [];
    $vars['brand'] = $brand;

    $smarty_view->assign($vars);
  }

  /**
   * extract category data from the system
   * @param int $category_id
   * @return mixed
   */
  function getCategoryData($category_id)
  {
    $cache_key = "category_" . $category_id;
    $category = $this->getVolotileCache($cache_key);
    if (is_null($category)) {
      $category = wa()->getView()->getVars('category');
      if (!(is_array($category) && isset($category['id']) && $category['id'] == $category_id)) {
        $category = (new shopCategoryModel())->getById($category_id); // $category could be null
      }
      $this->setVolotileCache($cache_key, $category);
    }

    return $category;
  }

  /**
   * extract product data from the system
   * @param int $product_id
   * @return mixed
   */
  function getProductData($product_id)
  {
    $cache_key = "product_" . $product_id;
    $product = $this->getVolotileCache($cache_key);

    if (is_null($product)) {
      $product = wa()->getView()->getVars('product');
      if (!(isset($product) && (is_array($product) || $product instanceof shopProduct) && isset($product['id']) && $product['id'] == $product_id)) {
        $product = new shopProduct($product_id);
        if (!$product->getId()) {
          $product = null;
        }
      }
      $this->setVolotileCache($cache_key, $product);
    }

    return $product;
  }

  /**
   * Inject additional data into category
   * @param array $category
   * @param array $include_parent_categories
   * @return array
   */
  function extendCategory(&$category, $include_parent_categories)
  {
    if ($include_parent_categories) {
      $path_categories = (new shopCategoryModel())->getPath($category['id']);
      $parent_categories = array_reverse($path_categories);

      foreach ($parent_categories as $i => $parent_category) {
        $parent_categories[$i] = $this->extendCategory(
          $parent_category,
          false
        );
      }

      $category['parents'] = $parent_categories;
    }

    $seo_name = '';
    $fields = ['', '', ''];

    if ($seo_name === '') {
      $seo_name = $category['name'];
    }

    $category['seo_name'] = $seo_name;
    $category['fields'] = $fields;

    $category = array_merge(
      $category,
      $this->getCategoryProductsData($category['id'])
    );

    if (!array_key_exists('params', $category) || !is_array($category['params'])) {
      $category['params'] = (new shopCategoryParamsModel())->get($category['id']);
    }

    return $category;
  }

  /**
   * Inject additional data into product
   * @param mixed $product
   * @return mixed
   */
  function extendProduct(&$product)
  {
    $seo_name = $product['name'];

    $product['seo_name'] = $seo_name;
    $product['format_price'] = shop_currency($product['price']);

    if (isset($product['skus'])) {
      $product['sku'] = $product['skus'][$product['sku_id']]['sku'];
    } else {
      $product['sku'] = null;
    }

    return $product;
  }

  /**
   * load products data that are included into category
   * @param int $category_id
   * @return array
   */
  function getCategoryProductsData($category_id)
  {
    $config = wa('shop')->getConfig();
    $default_currency = $config->getCurrency(true);
    $frontend_currency = $config->getCurrency(false);

    $products = new shopEasyseoProductsCollection('category/' . $category_id);

    $result = array();
    $result['products_count'] = $products->count();


    $range = $products->getPriceRange();

    $range['min'] = $this->roundPrice($range['min'], $default_currency, $frontend_currency);
    $result['min_price'] = shop_currency($range['min'], $default_currency, $frontend_currency);
    $result['min_price_without_currency'] = shop_currency($range['min'], $default_currency, $frontend_currency, false);

    $range['max'] = $this->roundPrice($range['max'], $default_currency, $frontend_currency);
    $result['max_price'] = shop_currency($range['max'], $default_currency, $frontend_currency);
    $result['max_price_without_currency'] = shop_currency($range['max'], $default_currency, $frontend_currency, false);

    return $result;
  }

  /**
   * round the price
   * @param float $price
   * @param string $default_currency
   * @param string $frontend_currency
   * @return float
   */
  private function roundPrice($price, $default_currency, $frontend_currency)
  {
    $config = wa('shop')->getConfig();
    $currencies_config = $config->getCurrencies();

    if ($price > 0) {
      if (!empty($currencies_config[$frontend_currency]['rounding']) && $default_currency != $frontend_currency) {
        $frontend_price = shop_currency($price, $default_currency, $frontend_currency, false);

        $frontend_price = shopRounding::roundCurrency($frontend_price, $frontend_currency);
        $price = shop_currency($frontend_price, $frontend_currency, $default_currency, false);
      }
    }

    return $price;
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
    return new waSerializeCache('easyseo/templates/' . $cache_key, $this->cache_valid_for * (0.01 * rand(-20, 20) + 1), 'shop');
  }

}