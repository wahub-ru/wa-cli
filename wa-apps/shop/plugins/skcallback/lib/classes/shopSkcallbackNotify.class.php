<?php


class shopSkcallbackNotify{

    protected $plugin_id = "skcallback";

    protected $defines = array();

    protected $params = array();

    public function __construct($request_id){

        $definesModel = new shopSkcallbackDefinesModel();
        $this->defines = $definesModel->getDefines();

        if(!$this->defines["email_active"] && !$this->defines["sms_active"] && !$this->defines["push_active"]){
            return ;
        }

        $requestModel = new shopSkcallbackRequestsModel();
        $request = $requestModel->getById($request_id);

        $valuesModel = new shopSkcallbackValuesModel();
        $request["values"] = $valuesModel->getValues($request_id);

        $controlsModel = new shopSkcallbackControlsModel();
        $request["controls"] = $controlsModel->getControlsWithTypes();

        $this->params = $this->getParams($request);

    }

    protected function getParams($request){

        $params = array(
            "id" => $request["id"],
            "region" => $request["region"],
            "city" => $request["city"],
            "url" => $request["referrer"],
        );

        foreach($request["controls"] as $control_id => $control){
            $params["var_{$control_id}"] = isset($request["values"][$control_id]["value"]) ? $request["values"][$control_id]["value"] : "";
            if($control["type_name"] == "slider" && $params["var_{$control_id}"]){
                $params["var_{$control_id}"] = str_replace(",", ":00 - ", $params["var_{$control_id}"] . ":00");
            }
        }

        return $params;

    }

    public function sendEmail(){

        $plugin_id = $this->plugin_id;
        $defines = $this->defines;

        if(!$defines["email_active"]){
            return false;
        }

        $params = $this->params;

        $emails_to = explode(",", $defines["email_list"]);
        if(empty($emails_to)){
            waLog::log("Не задан email получателя", "shop/plugins/{$plugin_id}.log");
            return false;
        }

        $subject = $defines["email_title"];
        if(!$subject){
            $subject = "Заявка на обратный звонок";
        }

        $body = $defines["email_content"];

        $view = wa()->getView();

        $view->assign($params);
        $view->assign("skcallback_eval", $subject);
        $path = wa()->getAppPath() . "/plugins/{$plugin_id}";

        $subject = $view->fetch($path . '/templates/actions/notify/eval.html');

        $view->assign("skcallback_eval", $body);
        $body = $view->fetch($path . '/templates/actions/notify/eval.html');

        $mail_message = new waMailMessage($subject, $body);

        $from_email = wa()->getSetting("email", "", "shop");
        $from_name = wa()->getSetting("name", "Вебасист", "shop");

        if(!$from_email){
            waLog::log("Не задан email отправителя", "shop/plugins/{$plugin_id}.log");
            return false;
        }

        $mail_message->setFrom($from_email, $from_name);

        foreach($emails_to as $email){
            $email = trim($email);
            $mail_message->addTo($email);
        }

        $mail_message->send();

    }

    public function sendSms(){

        $plugin_id = $this->plugin_id;
        $defines = $this->defines;

        if(!$defines["sms_active"]){
            return false;
        }

        $params = $this->params;

        $phones_to = explode(",", $defines["sms_list"]);
        if(empty($phones_to)){
            waLog::log("Не задан номер телефона получателя", "shop/plugins/{$plugin_id}.log");
            return false;
        }

        $sms_content = $defines["sms_content"];

        $view = wa()->getView();
        $view->assign($params);
        $view->assign("skcallback_eval", $sms_content);
        $path = wa()->getAppPath() . "/plugins/{$plugin_id}";

        $sms_content = $view->fetch($path . '/templates/actions/notify/eval.html');

        $sms_message = new waSMS();
        if(!$sms_message->adapterExists()){
            waLog::log("Адаптер смс сервиса недоступен", "shop/plugins/{$plugin_id}.log");
            return false;
        }

        foreach($phones_to as $phone){
            $phone = trim($phone);
            $sms_message->send($phone, $sms_content);
        }
    }

    public function sendPush(){
        $plugin_id = $this->plugin_id;
        $defines = $this->defines;

        if(!$defines["push_active"]){
            return false;
        }

        $params = $this->params;

        $subject = $defines["push_title"];
        if(!$subject){
            $subject = "Заявка на обратный звонок";
        }

        $push_content = $defines["push_content"];

        $view = wa()->getView();
        $view->assign($params);
        $view->assign("skcallback_eval", $subject);
        $path = wa()->getAppPath() . "/plugins/{$plugin_id}";

        $subject = $view->fetch($path . '/templates/actions/notify/eval.html');

        $view->assign("skcallback_eval", $push_content);

        $push_content = $view->fetch($path . '/templates/actions/notify/eval.html');

        try{

            $push = wa()->getPush();
            if (!$push->isEnabled()) {
                return false;
            }

            $data = array(
                'title'   => $subject,
                'message' => $push_content,
                'url'     => "/shop/?plugin=skcallback",
            );

            $contact_rights_model = new waContactRightsModel();
            $shop_user_ids = $contact_rights_model->getUsers('shop');

            $push->sendByContact($shop_user_ids, $data);

        }catch(Exception $ex){

            $result = $ex->getMessage();
            waLog::log("Unable to send PUSH notifications: ".$result, "shop/plugins/{$plugin_id}.log");
            return false;

        }

    }

}