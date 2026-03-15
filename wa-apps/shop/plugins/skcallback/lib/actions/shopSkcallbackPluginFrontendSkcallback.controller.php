<?php

class shopSkcallbackPluginFrontendSkcallbackController extends waJsonController{

    protected $plugin_id = "skcallback";

    public function execute(){

        $plugin_id = $this->plugin_id;

        $post = waRequest::post();

        $dataPost = array();

        if(!isset($post["check"]) || !$post["check"]){
            return false;
        }

        foreach($post["sk_callback_form"] as $id => $value){
            $id = (int)$id;
            $dataPost[$id] = $value;
        }

        $controlsModel = new shopSkcallbackControlsModel();

        $controls = $controlsModel->getControlsWithTypes();

        $errors = array();
        $values = array();

        $definesModel = new shopSkcallbackDefinesModel();
        $defines = $definesModel->getDefines();

        if(!empty($defines["captcha"])){
            if(!wa()->getCaptcha()->isValid()){
                $errors['captcha'] = "Капча введена неверно";
            }
        }

        foreach($controls as $id => $control){

            if($control["require"] && (!isset($dataPost[$id]) || empty($dataPost[$id]))){
                $errors[$id] = "Поле обязательно для заполнения";
                continue;
            }

            if(isset($dataPost[$id])){
                if($control["type_name"] == "slider" && is_array($dataPost[$id])){
                    $values[$id] = implode(",", $dataPost[$id]);
                }else{
                    $values[$id] = $dataPost[$id];
                }
                if($control["type_name"] == "email" && $dataPost[$id] && !filter_var($dataPost[$id], FILTER_VALIDATE_EMAIL)){
                    $errors[$id] = "Неправильный формат Email";
                }
            }else{
                $values[$id] = "";
            }

        }

        if(empty($errors)){

            $dataParams = $this->getRegion();

            $requestsModel = new shopSkcallbackRequestsModel();
            $valuesModel = new shopSkcallbackValuesModel();
            $cartModel = new shopSkcallbackCartModel();

            //Сохраняем форму
            $dataRequest = array(
                "status_id" => 1,
                "customer_id" => (int)wa()->getUser()->getId(),
                "date" => date("Y-m-d H:i:s"),
                "referrer" => waRequest::server("HTTP_REFERER"),
                "region" => $dataParams["region"],
                "city" => $dataParams["city"],
                "ip" => waRequest::getIp(),
            );

            $request_id = $requestsModel->insert($dataRequest);

            $dataValues = array();
            foreach($values as $control_id => $value){
                if(!$value){
                    continue;
                }
                $dataValues[] = array(
                    "request_id" => $request_id,
                    "control_id" => $control_id,
                    "value" => $value,
                );
            }

            if($dataValues){
                $valuesModel->multipleInsert($dataValues);
            }

            //Сохраняем текущую корзину
            $cart = new shopCart();
            $items = $cart->items();

            $dataCart = array();
            if(!empty($items)){
                foreach($items as $item){
                    $dataCart[] = array(
                        "request_id" => $request_id,
                        "product_id" => $item["product_id"],
                        "sku_id" => $item["sku_id"],
                        "quantity" => $item["quantity"],
                    );
                }
            }

            if($dataCart){
                $cartModel->multipleInsert($dataCart);
            }

            $view = wa()->getView();
            $definesModel = new shopSkcallbackDefinesModel();
            $defines = $definesModel->getDefines();
            $view->assign("defines", $defines);
            $path = wa()->getAppPath() . "/plugins/{$plugin_id}";
            $content = $view->fetch($path . '/templates/actions/frontend/Success.html');

            $this->response = array("content" => $content);

            $notifyModel = new shopSkcallbackNotify($request_id);
            $notifyModel->sendEmail();
            $notifyModel->sendSms();
            $notifyModel->sendPush();

            return true;
        }

        $this->errors = $errors;
        return false;

    }

    public function getRegion(){

        $plugin_id = $this->plugin_id;

        $search_region = (int)wa("shop")->getPlugin($plugin_id)->getSettings("search_region");

        $ip = waRequest::getIp();

        $params = array(
            "region" => "",
            "city" => "",
        );

        if($search_region && $ip){

            $url = "http://ipgeobase.ru:7020/geo?ip={$ip}";

            $ch = curl_init();

            $options = array(
                CURLOPT_URL => $url,
                CURLOPT_CUSTOMREQUEST => "GET",
                CURLOPT_CONNECTTIMEOUT => 2,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_SSL_VERIFYPEER => false,
                CURLOPT_SSL_VERIFYHOST => false,
            );

            curl_setopt_array($ch, $options);

            $response = curl_exec($ch);
            $error = curl_error($ch);

            $xml = simplexml_load_string($response);

            if(!$error && $xml !== false){

                $params["region"] = (string)$xml->ip->region;
                $params["city"] = (string)$xml->ip->city;

            }

        }

        return $params;

    }

}