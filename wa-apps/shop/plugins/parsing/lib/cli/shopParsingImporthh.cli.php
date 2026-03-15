<?php 

class shopParsingImporthhCli extends waCliController
{
    public function execute()
    {            
        $argv = waRequest::server('argv');
        $_GET['profile_id'] = $argv[3];
        
        $action = new shopParsingPluginBackendImporthhController();
        $result = $action->execute();
    }

}