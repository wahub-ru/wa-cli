<?php

class shopSkcallbackPluginBackendReloadRequestsController extends waJsonController{

    protected $plugin_id = "skcallback";

    public function execute(){

        $plugin_id = $this->plugin_id;

        $settings = wa("shop")->getPlugin($plugin_id)->getSettings();

        $post = waRequest::post();

        $filters = $post["filter"];
        $page = isset($post["page"]) && (int)$post["page"] ? (int)$post["page"] : 1;

        $requestModel = new shopSkcallbackData();

        $limit = (int)$settings["request_pagination"] ? (int)$settings["request_pagination"] : 30;

        $data = $requestModel->getData($page, $limit, $filters);

        $view = wa()->getView();

        $view->assign("skcallback_data", $data);
        $view->assign("skcallback_settings", $settings);

        $path = wa()->getAppPath() . "/plugins/{$plugin_id}";
        $content = $view->fetch($path . '/templates/actions/backend/TemplateTable.html');

        $this->response = array("content" => $content, "count" => $data["count"], "is_add" => $data["is_add"]);

        return true;

    }

}