<?php

class shopParsingPluginBackendClearController extends waJsonController
{
    public function execute()
    {
        
        try {
            $mysql = new waModel();
            $mysql->query("DELETE FROM shop_parsing_plugin_sitemap WHERE profile_id = '".$mysql->escape(waRequest::get('profile_id'))."'");
            
        } catch(waException $e) {
            $this->setError($e->getMessage());
        }
    }
}