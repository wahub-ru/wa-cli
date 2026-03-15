<?php

class shopParsingPluginBackendKeepController extends waJsonController
{
    public function execute()
    {
        
        try {
            $mysql = new waModel();
            $mysql->query("DELETE FROM shop_parsing_plugin_sitemap WHERE profile_id = '".$mysql->escape(waRequest::get('profile_id'))."' AND (product_id IS NULL OR product_id = 0)");
            
        } catch(waException $e) {
            $this->setError($e->getMessage());
        }
    }
}