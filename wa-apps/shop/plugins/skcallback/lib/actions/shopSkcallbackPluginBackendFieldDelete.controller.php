<?php

class shopSkcallbackPluginBackendFieldDeleteController extends waJsonController{

    public function execute(){

        $control_id = (int)waRequest::post("control_id");

        if(!$control_id){
            return false;
        }

        $controlsModel = new shopSkcallbackControlsModel();

        $controlsModel->deleteById($control_id);

        return true;

    }

}