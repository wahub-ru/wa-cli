<?php


class shopSkcallbackHelper{

    const plugin_id = "skcallback";

    public static function isActive(){

        $active = wa("shop")->getPlugin("skcallback")->getSettings("active");

        if($active){
            return true;
        }else{
            return false;
        }


    }

    public static function getForm(){

        $settings = wa("shop")->getPlugin(self::plugin_id)->getSettings();

        $definesModel = new shopSkcallbackDefinesModel();
        $definesData = $definesModel->getDefines();

        $controlsModel = new shopSkcallbackControlsModel();
        $controlsData = $controlsModel->getControlsWithTypes();

        $paramsInit = array(
            "urlSave" => wa()->getRouteUrl("shop/frontend/skcallback/"),
            "yandexId" => $definesData["yandex_number"],
            "yandexOpen" => $definesData["yandex_open"],
            "yandexSend" => $definesData["yandex_send"],
            "yandexError" => $definesData["yandex_error"],
            "googleOpenCategory" => $definesData["goggle_open_category"],
            "googleOpenAction" => $definesData["goggle_open_action"],
            "googleSendCategory" => $definesData["goggle_send_category"],
            "googleSendAction" => $definesData["goggle_send_action"],
            "googleErrorCategory" => $definesData["goggle_error_category"],
            "googleErrorAction" => $definesData["goggle_error_action"],
        );

        $view = wa()->getView();
        $view->assign("skcallback_settings", $settings);
        $view->assign("skcallback_defines", $definesData);
        $view->assign("skcallback_controls", $controlsData);
        $view->assign("skcallback_init", $paramsInit);
        $view->assign("shopSkCallbackPathJS", wa("shop")->getPlugin(self::plugin_id)->getPluginStaticUrl() . "js/");

        $path = wa()->getAppPath(null, "shop") . "/plugins/" . self::plugin_id;
        $content = $view->fetch($path . '/templates/actions/frontend/Form.html');

        return $content;

    }

}