<?php

class shopSkcallbackValuesModel extends waModel{

    protected $table = 'shop_skcallback_values';

    public function getValues($request_id){

        $request_id = (int)$request_id;

        $values = $this->query("SELECT * FROM shop_skcallback_values WHERE request_id = {$request_id}")->fetchAll();

        $result = array();

        foreach($values as $value){
            $result[$value["control_id"]] = $value;
        }

        return $result;

    }

}
