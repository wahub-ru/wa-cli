<?php

class shopSkcallbackPluginBackendFieldAddController extends waJsonController{

    protected $plugin_id = "skcallback";

    public function execute(){

        $plugin_id = $this->plugin_id;

        $type_id = (int)waRequest::post("type_id");
        $max_id = (int)waRequest::post("max_id");

        if(!$type_id){
            $this->errors[] = "Ошибка при передаче типа поля";
            return false;
        }

        if(!$max_id){
            $max_id = 1;
        }

        $controlsTypeModel = new shopSkcallbackControlsTypeModel();
        $controlsType = $controlsTypeModel->getByField(array("id" => $type_id));

        $view = wa()->getView();
        $path = wa()->getAppPath() . "/plugins/{$plugin_id}";

        $control = array(
            "id" => $max_id,
            "type_id" => $type_id,
            "type_name" => $controlsType["name"],
            "title" => $controlsType["title"],
            "placeholder" => $controlsType["placeholder"],
            "is_additional" => $controlsType["is_additional"],
            "additional" => "",
            "is_require" => $controlsType["is_require"],
            "require" => 0,
        );

        $view->assign("control", $control);

        $content = $view->fetch($path . '/templates/actions/settings/SettingsField.html');

        $this->response = array("content" => $content);

        return true;

    }

}