<?php

class shopSkcallbackPluginSettingsAction extends waViewAction{

    protected $plugin_id = "skcallback";

    public function execute(){

        $plugin_id = $this->plugin_id;

        /*Настройки плагина*/
        $vars = array();

        $plugin = waSystem::getInstance()->getPlugin($plugin_id, true);
        $namespace = wa()->getApp() . '_' . $plugin_id;

        $params = array();
        $params['id'] = $plugin_id;
        $params['namespace'] = $namespace;
        $params['title_wrapper'] = '%s';
        $params['description_wrapper'] = '<br><span class="hint">%s</span>';
        $params['control_wrapper'] = '<div class="name">%s</div><div class="value">%s %s</div>';

        $settings_controls = $plugin->getControls($params);
        $this->getResponse()->setTitle(_w(sprintf('Plugin %s settings', $plugin->getName())));

        $vars['plugin_info'] = array(
            'name' => $plugin->getName()
        );
        $vars['plugin_id'] = $plugin_id;
        $vars['settings_controls'] = $settings_controls;
        $vars['settings'] = $plugin->getSettings();

        $controlsTypeModel = new shopSkcallbackControlsTypeModel();
        $vars["controls_type"] = $controlsTypeModel->getAll();

        $controlsModel = new shopSkcallbackControlsModel();
        $vars["controls"] = $controlsModel->getControlsWithTypes();

        $definesModel = new shopSkcallbackDefinesModel();
        $vars["defines"] = $definesModel->getDefines();

        $statusModel = new shopSkcallbackStatusModel();
        $vars["statuses"] = $statusModel->getAll();

        $dataMax = $controlsModel->query("SELECT max(id) as max_id FROM shop_skcallback_controls")->fetchAssoc();
        $dataStatusMax = $controlsModel->query("SELECT max(id) as max_id FROM shop_skcallback_status")->fetchAssoc();
        $vars["shop_plugin_config"] = array(
            "max_id" => $dataMax["max_id"],
            "status_max_id" => $dataStatusMax["max_id"]
        );

        $this->view->assign($vars);

        $this->view->assign('shop_plugin_url', wa("shop")->getPlugin($plugin_id)->getPluginStaticUrl());

    }

}
