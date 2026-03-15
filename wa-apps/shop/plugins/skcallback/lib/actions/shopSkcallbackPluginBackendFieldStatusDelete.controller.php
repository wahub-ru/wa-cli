<?php

class shopSkcallbackPluginBackendFieldStatusDeleteController extends waJsonController{

    public function execute(){

        $status_id = (int)waRequest::post("status_id");

        if(!$status_id){
            return false;
        }

        $statusModel = new shopSkcallbackStatusModel();

        $statusModel->deleteById($status_id);

        return true;

    }

}