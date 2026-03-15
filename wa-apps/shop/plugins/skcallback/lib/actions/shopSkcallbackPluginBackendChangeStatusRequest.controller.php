<?php

class shopSkcallbackPluginBackendChangeStatusRequestController extends waJsonController{

    public function execute(){

        $status_id = (int)waRequest::post("status_id");
        $request_id = (int)waRequest::post("request_id");

        if(!$status_id || !$request_id){
            $this->errors[] = "Некорректные данные";
            return false;
        }

        $requestModel = new shopSkcallbackRequestsModel();

        $requestModel->updateByField(array("id" => $request_id), array("status_id" => $status_id));

        return true;

    }

}