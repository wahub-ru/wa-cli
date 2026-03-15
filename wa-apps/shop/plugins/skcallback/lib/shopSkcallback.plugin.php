<?php

class shopSkcallbackPlugin extends shopPlugin{

    public $plugin_id = "skcallback";


    public function backendMenu(){

        $plugin_id = $this->plugin_id;

        $settings = wa("shop")->getPlugin($plugin_id)->getSettings();

        if(!$settings["active"]){
            return array();
        }

        $current = waRequest::get("plugin");
        $selected = "no-tab";
        if($current == $plugin_id){
            $selected = "selected";
        }

        $counter = "";
        if((int)$settings["menu_counter"] && $current != $plugin_id){
            $date = date("d.m.Y");

            $dataObject = new shopSkcallbackData();
            $where = $dataObject->getFiltersSql(array("date_from" => $date, "date_to" => $date, "status" => 1));
            $cnt = $dataObject->getCounters($where);

            if($cnt){
                $counter = "<sup class='red' style='display:inline'>{$cnt}</sup>";
            }
        }

        $menu = array(
            "core_li" => "<li class='{$selected}'><a href='?plugin=skcallback'>{$settings["menu_title"]}{$counter}</a></li>",
        );

        return $menu;

    }

}
