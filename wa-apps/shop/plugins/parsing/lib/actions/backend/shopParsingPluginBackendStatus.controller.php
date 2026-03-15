<?php

class shopParsingPluginBackendStatusController extends waJsonController
{    
    public function execute()
    {
        $mysql = new waModel();
        
        $id = waRequest::get('id');
        $status = waRequest::get('status');
        
        if($status == 'true'){
            $mysql->query("UPDATE shop_parsing_plugin_sitemap SET status = 1 WHERE id = '".$mysql->escape($id)."'");
        }else{
            $mysql->query("UPDATE shop_parsing_plugin_sitemap SET status = 0 WHERE id = '".$mysql->escape($id)."'");
        }
        
        $this->response = $status;
    }
}