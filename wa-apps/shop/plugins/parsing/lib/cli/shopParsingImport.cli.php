<?php 

class shopParsingImportCli extends waCliController
{
    public function execute()
    {            
		waLog::log("Запуск парсинга!", "shop/parsing_debug.log");
        $argv = waRequest::server('argv');
        $_GET['profile_id'] = $argv[3];
        
        $action = new shopParsingPluginBackendImportController();
        $result = $action->execute();
		waLog::log("Парсинг отработал!", "shop/parsing_debug.log");
    }

}