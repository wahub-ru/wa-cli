<?php

class blogHidsetPluginBackendSaveController extends waJsonController {


    public function execute(){
        $plugin = wa()->getPlugin('hidset');
        $hsets = $plugin->hsets;
        $allsets = wa('blog')->getConfig()->getOption(null);
        $error = $check_error = false;
        $data = waRequest::post();
        foreach ($data as $key => $value) {
            switch ($hsets[$key]['type']) {
                case 'int':
                    if (self::checkInt($key, $value)) {
                        $error .= $key . ' ';
                        $check_error = true;
                    } else {
                        $allsets[$key] = $data[$key] = intval($value);
                    }
                    break;
                case 'select':
                    if($value === 'true'){
                        $value = true;
                    }
                    if($value === 'false'){
                        $value = false;
                    }
                    $allsets[$key] = $value;
                    unset($data[$key]);
                    break;
                case 'array':
                    foreach ($value as $akey => $avalue) {
                        if (self::checkInt($key, $avalue)) {
                            $error .= $key . ' ';
                            $check_error = true;
                            break 2;
                        } else {
                            $allsets[$key][$akey] = $data[$key][$akey] = intval($avalue);
                        }
                    }
                    break;
            }
        }
        
        if (!$check_error) {
            $config = $this->getConfig();
            $config_file = $config->getConfigPath('config.php');
            waUtils::varExportToFile($allsets, $config_file);
        } else {
            $this->setError($error);
        }
        
        $this->response = $data;
    }


    private function checkInt($key, $value){
        $plugin = wa()->getPlugin('hidset');
        if (!(int)$value || (int)$value < 0 || (isset($plugin->hsets[$key]['limit']) && $value > $plugin->hsets[$key]['limit'])) {
            return $key;
        } else {
            return false;
        }
    }
}