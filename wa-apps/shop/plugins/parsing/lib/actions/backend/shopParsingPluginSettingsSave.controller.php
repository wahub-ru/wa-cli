<?php

class shopParsingPluginSettingsSaveController extends waJsonController
{
    public function execute()
    {
        
        try {
            $profile_config = waRequest::post('profile');
            
            $profiles = new shopImportexportHelper('parsing');
            $profile_id = $profiles->setConfig($profile_config);
        } catch(waException $e) {
            $this->setError($e->getMessage());
        }
    }
}
