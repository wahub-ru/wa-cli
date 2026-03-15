<?php
/*
 * @link https://warslab.ru/
 * @author waResearchLab
 * @Copyright (c) 2023 waResearchLab
 */

class shopHidsetPluginBackendActions extends shopHidsetPluginJsonActions
{
    public function saveSettingsAction()
    {
        if (!$shop_settings = waRequest::post('shop')) {
            $this->setError(sprintf(shopHidsetPlugin::ERROR_REQUIRED_PARAM, 'shop settings'));
            return;
        }
        $hidset = wa()->getPlugin('hidset');
        foreach ($shop_settings as $key => $value) {
            if (isset($hidset->hsets[$key])) {
                $shop_settings[$key] = $this->checkValue($hidset->hsets, $key, $value);
            } else {
                unset($shop_settings[$key]);
            }
        }
        if ($this->errors) return;
        $config = $this->getConfig();
        try {
            $current = wa('shop')->getConfig()->getOption(null);
            $current = array_merge($current, $shop_settings);
            waUtils::varExportToFile($current, $config->getConfigPath('config.php'));
        } catch (Exception $e) {
            $this->setError($e->getMessage());
            return;
        }
        if ($plugin_settings = waRequest::post('plugins', [])) {
            foreach ($plugin_settings as $idx => $plugin_sets) {
                $plugin_id = $plugin_sets['plugin_id'];
                $index = array_search($plugin_id, array_column($hidset->plugins, 'plugin_id'));
                if ($index === false) {
                    unset ($plugin_settings[$idx]);
                    continue;
                }
                foreach ($plugin_sets['config'] as $set_id => $pvalue) {
                    if (isset($hidset->plugins[$index]['sets'][$set_id])) {
                        $plugin_settings[$idx]['config'][$set_id] = $this->checkValue($hidset->plugins[$index]['sets'], $set_id, $pvalue);
                    } else {
                        unset($plugin_settings[$plugin_id]['config'][$set_id]);
                    }
                }
                $plugin_config_path = wa('shop')->getConfigPath('shop/plugins/' . $plugin_id . '/config.php');
                if (!realpath(dirname($plugin_config_path))) {
                    waFiles::create(wa('shop')->getConfigPath('shop/plugins/' . $plugin_id));
                }
                if ($this->errors) continue;
                try {
                    $plugin_config = $hidset->getPluginConfig($plugin_id);
                    $plugin_config = array_merge($plugin_config, $plugin_settings[$idx]['config']);
                    waUtils::varExportToFile($plugin_config, $plugin_config_path);
                } catch (Exception $e) {
                    $this->setError($e->getMessage());
                }
            }
        }
        unset ($hidset);
        $this->response = [
            'shop' => $current,
            'plugins' => wa()->getPlugin('hidset')->plugins
        ];
    }

    public function restoreDefaultSettingsAction()
    {
        $type = waRequest::post('type');
        $type_settings = waRequest::post('set_type');
        if ($type_settings === 'icons') $type = 'icons';
        $true_types = ['shop_hidset', 'shop', 'plugins', 'all'];
        if (!$type || ($type_settings !== 'icons' && !in_array($type, $true_types))) {
            $this->setError(sprintf(shopHidsetPlugin::ERROR_REQUIRED_PARAM, 'type'));
            return;
        }
        $config = $this->getConfig();
        $current = include($config->getConfigPath('config.php'));
        $default = include($config->getConfigPath('config.php', false));
        $plugin_sets = include(wa()->getAppPath('plugins/hidset/lib/config/data/sets.php'));
        if ($type_settings !== 'icons') {
            foreach ($plugin_sets as $s => $pset) {
                if ($pset['type'] === 'icons') $default[$s] = $current[$s];
            }
        }
        try {
            switch ($type) {
                case 'shop_hidset':
                    $sets = include(wa()->getAppPath('plugins/hidset/lib/config/data/sets.php'));
                    foreach ($current as $key => $value) {
                        if (isset($sets[$key])) {
                            $current[$key] = $default[$key];
                        }
                    }
                    waUtils::varExportToFile($current, $config->getConfigPath('config.php'));
                    break;
                case 'shop':
                    $current = array_merge($current, $default);
                    waUtils::varExportToFile($current, $config->getConfigPath('config.php'));
                    break;
                case 'all':
                    waUtils::varExportToFile($default, $config->getConfigPath('config.php'));
                    $current = $default;
                case 'plugins':
                    $plugin = wa()->getPlugin('hidset');
                    $shop_plugins = wa()->getConfig()->getPlugins();
                    $plugins = wa()->getPlugin('hidset')->plugins;
                    foreach ($plugins as $idx => $data) {
                        $plugin_id = $data['plugin_id'];
                        if (!isset($shop_plugins[$plugin_id])) continue;
                        $pdefault_file = wa('shop')->getAppPath('plugins/' . $plugin_id, 'shop') . '/lib/config/config.php';
                        $pcustom_file = wa('shop')->getConfigPath('shop/plugins/' . $plugin_id) . '/config.php';
                        $pconfig = include($pdefault_file);
                        waUtils::varExportToFile($pconfig, $pcustom_file);
                    }
                    unset($plugin);
                    break;
                case 'icons':
                    foreach ($plugin_sets as $s => $pset) {
                        if ($pset['type'] === 'icons') {
                            $current[$s] = $default[$s];
                        }
                    }
                    waUtils::varExportToFile($current, $config->getConfigPath('config.php'));
                    break;
            }
        } catch (Exception $e) {
            $this->setError($e->getMessage());
        }
        $this->response = [
            'shop' => $current,
            'plugins' => wa()->getPlugin('hidset')->plugins
        ];
    }

    public function restoreDefaultDimensionAction()
    {
        $this->checkPremium();
        if ($this->errors) return;
        if (!$type = waRequest::post('type')) {
            $this->setError(sprintf(shopHidsetPlugin::ERROR_REQUIRED_PARAM, 'type'));
            return;
        }
        $hidset = wa()->getPlugin('hidset');
        $dimensions = $hidset->getDimensions();
        $default_dimensions = include(wa()->getAppPath('lib/config/data/dimension.php'));
        if (!isset($dimensions[$type])) {
            $this->setError(sprintf(shopHidsetPlugin::ERROR_UNKNOWN_DIM_TYPE, $type));
            return;
        }
        $dimensions[$type] = $default_dimensions[$type];
        waUtils::varExportToFile($dimensions, wa()->getConfig()->getConfigPath('dimension.php'));
        $this->response = $dimensions;
    }

    public function saveDimAction()
    {
        $this->checkPremium();
        if ($this->errors) return;
        $dim = waRequest::post('dim');
        $type = waRequest::post('type');
        if (!$dim || !$type) {
            $this->setError(sprintf(shopHidsetPlugin::ERROR_REQUIRED_PARAM, 'dimension'));
            return;
        }
        try {
            $hidset = wa()->getPlugin('hidset');
            $dimensions = $hidset->getDimensions();
            $dimensions[$type] = $dim;
            waUtils::varExportToFile($dimensions, wa()->getConfig()->getConfigPath('dimension.php'));
        } catch (waException $e) {
            $this->setError($e->getMessage());
        }
        $this->response = $dimensions;
    }

    public function delDimAction()
    {
        $this->checkPremium();
        if ($this->errors) return;
        if (!$id = waRequest::post('id')) {
            $this->setError(sprintf(shopHidsetPlugin::ERROR_REQUIRED_PARAM, 'id'));
            return;
        }
        $hidset = wa()->getPlugin('hidset');
        $default_dimensions = include(wa()->getAppPath('lib/config/data/dimension.php'));
        if (isset($default_dimensions[$id])) {
            $this->setError(shopHidsetPlugin::ERROR_UNABLE_DELETE_DEFAULT_DIM);
            return;
        }
        $dimensions = $hidset->getDimensions();
        unset($dimensions[$id]);
        try {
            waUtils::varExportToFile($dimensions, wa()->getConfig()->getConfigPath('dimension.php'));
        } catch (waException $e) {
            $this->setError($e->getMessage());
        }
        $this->response = $dimensions;
    }

    public function getDimensionsAction() {
        $this->response = wa()->getPlugin('hidset')->getDimensions();
    }

    public function runCliTaskAction()
    {
        $this->checkPremium();
        if ($this->errors) return;
        if (!$task = waRequest::post('task')) {
            $this->setError(sprintf(shopHidsetPlugin::ERROR_REQUIRED_PARAM, 'task'));
            return;
        }
        $data = waRequest::post('data');
        $cli = new shopHidsetPluginCli();
        $cli->preExecute();
        if (!isset($cli->task_map[$task])) {
            $this->setError(sprintf(shopHidsetPlugin::ERROR_UNKNOWN_TASK, $task));
            return;
        }
        try {
            $cli->task_map[$task]['task']->run($data);
        } catch (Exception $e) {
            $this->setError($e->getMessage());
            return;
        }
    }

    private function checkValue($sets, $key, $value)
    {
        switch ($sets[$key]['type']) {
            case 'int':
                if (wa_is_int($value)) {
                    $value = (int)$value;
                } else {
                    $value = null;
                    $this->setError(sprintf(shopHidsetPlugin::ERROR_VALUE_TYPE, $key));
                }
                break;
            case 'select':
                if ($value === 'true') {
                    $value = true;
                }
                if ($value === 'false') {
                    $value = false;
                }
                break;
            case 'array':
                foreach ($value as $akey => &$avalue) {
                    if (wa_is_int($avalue)) {
                        $avalue = (int)$avalue;
                    } else {
                        $value = null;
                        $this->setError(sprintf(shopHidsetPlugin::ERROR_VALUE_TYPE, $key));
                        break 2;
                    }
                }
                unset($avalue);
                break;
        }
        return $value;
    }
}