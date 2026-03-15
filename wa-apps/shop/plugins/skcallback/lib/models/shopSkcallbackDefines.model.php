<?php

class shopSkcallbackDefinesModel extends waModel{

    protected $table = 'shop_skcallback_defines';

    public function getDefines(){

        $definesArray = $this->getAll();
        $defines = array();

        foreach($definesArray as $data){
            $defines[$data["name"]] = $data["value"];
        }

        return $defines;

    }

}
