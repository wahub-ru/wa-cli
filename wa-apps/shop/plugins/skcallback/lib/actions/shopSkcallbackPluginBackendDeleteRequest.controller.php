<?php

class shopSkcallbackPluginBackendDeleteRequestController extends waJsonController{

    public function execute(){

        $request_id = (int)waRequest::post("request_id");

        if(!$request_id){
            $this->errors[] = "Некорректные данные";
            return false;
        }

        $requestModel = new shopSkcallbackRequestsModel();
        $requestModel->deleteById($request_id);

        $valuesModel = new shopSkcallbackValuesModel();
        $valuesModel->deleteByField("request_id", $request_id);

        $cartModel = new shopSkcallbackCartModel();
        $cartModel->deleteByField("request_id", $request_id);

        return true;

    }

}