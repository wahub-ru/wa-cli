<?php

class shopSkcallbackPluginBackendAction extends waViewAction{

    protected $plugin_id = "skcallback";

    public function execute(){

        $plugin_id = $this->plugin_id;

        $settings = wa("shop")->getPlugin($plugin_id)->getSettings();

        $this->setLayout(new shopBackendLayout());

        $this->setTemplate(wa()->getAppPath("plugins/{$plugin_id}/templates/actions/backend/Template.html"));

        $view = $this->view;

        $params = array(
            "shop_plugin_url" => wa("shop")->getPlugin($plugin_id)->getPluginStaticUrl(),
            "path_to_plugin" => wa()->getAppUrl('shop')
        );

        $view->assign("skcallback_params", $params);

        $requestModel = new shopSkcallbackData();

        $limit = (int)$settings["request_pagination"] ? (int)$settings["request_pagination"] : 30;

        $filters = array(
            "period" => "today",
            "date_from" => "",
            "date_to" => "",
            "status" => ""
        );

        $data = $requestModel->getData(1, $limit, $filters);

        $view->assign("skcallback_data", $data);
        $view->assign("skcallback_settings", $settings);

    }

}