<?php

class shopSkcallbackPluginBackendFieldStatusesAddController extends waJsonController{

    protected $plugin_id = "skcallback";

    public function execute(){

        $plugin_id = $this->plugin_id;

        $max_id = (int)waRequest::post("max_id");

        if(!$max_id){
            $max_id = 1;
        }

        $view = wa()->getView();
        $path = wa()->getAppPath() . "/plugins/{$plugin_id}";

        $status = array(
            "id" => $max_id,
            "title" => "Новый статус",
            "color" => "#000000",
            "starter" => 0,
        );

        $view->assign("status", $status);

        $content = $view->fetch($path . '/templates/actions/settings/SettingsFieldStatus.html');

        $this->response = array("content" => $content);

        return true;

    }

}