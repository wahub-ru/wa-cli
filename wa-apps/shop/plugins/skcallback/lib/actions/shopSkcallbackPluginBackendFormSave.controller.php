<?php

class shopSkcallbackPluginBackendFormSaveController extends waJsonController{

    public function execute(){

        $post = waRequest::post();
        $errors = array();

        if(isset($post["shop_skcallback"]) && !empty($post["shop_skcallback"])){
            wa("shop")->getPlugin("skcallback")->saveSettings($post["shop_skcallback"]);
        }

        if(isset($post["shop_skcallback_defines"]) && !empty($post["shop_skcallback_defines"])){
            $definesModel = new shopSkcallbackDefinesModel();
            foreach($post["shop_skcallback_defines"] as $name => $value){
                $definesModel->replace(array("name" => $name, "value" => $value));
            }
        }

        if(isset($post["shop_skcallback_status"]) && !empty($post["shop_skcallback_status"])){
            $definesModel = new shopSkcallbackStatusModel();
            foreach($post["shop_skcallback_status"] as $id => $status){
                $statusData = array(
                    "id" => $id,
                    "title" => $status["title"],
                    "color" => $status["color"],
                    "starter" => $status["starter"],
                );
                $definesModel->replace($statusData);
            }
        }

        if(isset($post["shop_skcallback_fields"]) && !empty($post["shop_skcallback_fields"])){

            $sort = 0;
            $dataControls = new shopSkcallbackControlsModel();

            foreach($post["shop_skcallback_fields"] as $id => $field){
                $field["title"] = trim($field["title"]);
                if(!$field["title"]){
                    $errors["fields"][$id] = "Задан пустой загаловок";
                    continue;
                }
                if(!isset($field["additional"])){
                    $field["additional"] = "";
                }elseif(is_array($field["additional"])){
                    $field["additional"] = implode(",", $field["additional"]);
                }
                if(!isset($field["require"])){
                    $field["require"] = 0;
                }
                $field["require"] = (int)$field["require"];

                $data = array(
                    "id" => $id,
                    "type_id" => $field["type_id"],
                    "title" => $field["title"],
                    "additional" => $field["additional"],
                    "require" => $field["require"],
                    "sort" => $sort,
                );

                $dataControls->replace($data);

                $sort++;
            }
        }

        if(!empty($errors)){
            $this->errors = $errors;
            return false;
        }

        return true;

    }


}