<?php

class shopSkcallbackControlsModel extends waModel{

    protected $table = 'shop_skcallback_controls';

    public function getControlsWithTypes(){

        $data = $this->query("SELECT t1.*, t2.name as type_name, t2.placeholder, t2.title as type_title, t2.is_require, t2.is_additional
             FROM shop_skcallback_controls t1
             JOIN shop_skcallback_controls_type t2 ON t1.type_id = t2.id
             ORDER BY t1.sort ASC")->fetchAll();

        $result = array();
        foreach($data as $item){
            if($item["type_name"] == "slider"){
                $item["additional"] = explode(",", $item["additional"]);
            }
            $item['id'] = (int)$item['id'];
            $result[$item['id']] = $item;
        }

        return $result;

    }

}
