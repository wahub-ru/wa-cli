<?php

class shopSkcallbackPluginBackendGetCartRequestController extends waJsonController{

    protected $plugin_id = "skcallback";

    public function execute(){

        $plugin_id = $this->plugin_id;

        $request_id = (int)waRequest::post("request_id");

        if(!$request_id){
            $this->errors[] = "Некорректные входные параметры";
            return false;
        }

        $cartModel = new shopSkcallbackCartModel();

        $products = $cartModel->getCartByRequestId($request_id);

        $view = wa()->getView();
        $path = wa()->getAppPath() . "/plugins/{$plugin_id}";

        $view->assign("skcallback_products", $products);

        $content = $view->fetch($path . '/templates/actions/backend/Cart.html');

        $this->response = array("content" => $content);

        return true;

    }

}