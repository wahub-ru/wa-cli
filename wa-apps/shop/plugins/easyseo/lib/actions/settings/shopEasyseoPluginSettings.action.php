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

  class shopEasyseoPluginSettingsAction extends waViewAction
  {
      /**
       * create setting page
       * @return void
       */
      public function execute()
      {
        $plugin_id = 'easyseo';
        $namespace = 'shop_'.$plugin_id;
        $plugins = $this->getConfig()->getPlugins();

        $plugin = waSystem::getInstance()->getPlugin($plugin_id, true);

        $params = array(
            'id'                  => $plugin_id,
            'namespace'           => $namespace,
            'title_wrapper'       => '%s',
            'description_wrapper' => '<br><span class="hint">%s</span>',
            'control_wrapper'     => '<div class="name">%s</div><div class="value">%s %s</div>',
            'subject'             => 'shop',
        );

        $this->view->assign('plugin_info', $plugins[$plugin_id]);
        $this->view->assign('plugin_id', $plugin_id);
        $this->view->assign('settings_controls', $plugin->getControls($params));


        $this->view->assign('css', 'css/easyseo.css');
        $this->view->assign('js', 'js/easyseo.js');
        $this->view->assign('plugin_root_path', '/wa-apps/shop/plugins');
      }
  }