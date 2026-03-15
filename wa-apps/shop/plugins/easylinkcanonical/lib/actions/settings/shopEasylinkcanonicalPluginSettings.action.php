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

  class shopEasylinkcanonicalPluginSettingsAction extends waViewAction
  {
      /**
       * create setting page
       * @return void
       */
      public function execute()
      {
        $plugin_id = 'easylinkcanonical';
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

        $this->view->assign('fajs', 'wa-content/js/fontawesome/fontawesome-all.min.js'); // fontawesome js / system js
        $this->view->assign('fajs2', 'wa-apps/installer/fonts/fontawesome/fontawesome-all.min.js'); // fontawesome js / system js on old installs

        $this->view->assign('css', 'css/easylinkcanonical.css');
        $this->view->assign('js', 'js/easylinkcanonical.js');
        $this->view->assign('plugin_root_path', 'wa-apps/shop/plugins');
      }
  }