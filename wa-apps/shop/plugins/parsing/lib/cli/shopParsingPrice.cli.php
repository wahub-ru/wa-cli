<?php 

class shopParsingPriceCli extends waCliController
{
    public function execute()
    {            
        $argv = waRequest::server('argv');
        $_GET['profile_id'] = $argv[3];
        
        $action = new shopParsingPluginBackendPriceController();
        $result = $action->execute();
    }

}