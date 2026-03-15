<?php

class shopHidsetPlugin extends shopPlugin
{
    const
        CLI_RUN_DONE = 'Выполнение задачи %s успешно завершено за %s сек',
        ERROR_REQUIRED_PARAM = 'Отсутствует обязательный параметр %s',
        ERROR_VALUE_TYPE = 'Некорректный тип значения %s',
        ERROR_CLI_TASK_RUN = 'Во время выполнения задачи %s произошла ошибка: %s',
        ERROR_NEED_PREMIUM = 'Для использования этой возможности необходимо наличие Премиум-лицензии плагина',
        ERROR_REQUIRED_PARAMETER = 'Отсутствует необходимый параметр %s',
        ERROR_UNABLE_DELETE_DEFAULT_DIM = 'Нельзя удалить меру входящую в поставку Shop-Script',
        ERROR_UNKNOWN_DIM_TYPE = 'Не удалось найти меру %s в штатной поставке Shop-Script',
        ERROR_UNKNOWN_TASK = 'Недопустимый параметр task: %s';

    const FILE_LOG = 'shop/plugins/hidset/hidset.log';
    public $hsets;
    public $plugins;

    public function __construct($info)
    {
        parent::__construct($info);

        $this->hsets = include($this->path . '/lib/config/data/sets.php');
        $this->plugins = [];
        $shop_plugins = wa()->getConfig()->getPlugins();
        $plugin_sets = include($this->path . '/lib/config/data/plugin_sets.php');
        foreach ($plugin_sets as $plugin_id => $psets) {
            if (!isset($shop_plugins[$plugin_id])) continue;
            $plugin = wa()->getPlugin($plugin_id);
            $config = $this->getPluginConfig($plugin_id);
            foreach ($config as $name => $value) {
                if (!isset($psets[$name])) unset($config[$name]);
            }
            $this->plugins[] = [
                'plugin_id' => $plugin_id,
                'name' => $plugin->getName(),
                'sets' => $psets,
                'config' => $config
            ];
        }
    }

    public function getPluginConfig($plugin_id)
    {
        $config = null;
        $files = array(
            wa('shop')->getAppPath('plugins/' . $plugin_id, 'shop') . '/lib/config/config.php', // defaults
            wa('shop')->getConfigPath('shop/plugins/' . $plugin_id) . '/config.php', // custom
        );
        $config = [];
        foreach ($files as $file_path) {
            if (file_exists($file_path)) {
                $config = array_merge($config, include($file_path));
            }
        }
        return $config;
    }

    public function getDimensions()
    {
        $config = wa('shop')->getConfig();
        $files = array(
            $config->getConfigPath('dimension.php'),
            $config->getConfigPath('data/dimension.php', false),
        );
        $dimensions = [];
        foreach ($files as $file_path) {
            if (file_exists($file_path)) {
                $dimensions = include($file_path);
                if ($dimensions && is_array($dimensions)) {
                    break;
                }
            }
        }
        return $dimensions;
    }

    public static function setLog($message, $data = null, $source = true)
    {
        $file = self::FILE_LOG;
        if ($source && function_exists('debug_backtrace')) {
            $bt = debug_backtrace(1)[0];
            $source = $bt['file'] . ' (' . $bt['line'] . ')' . ($message ? PHP_EOL . '------------------' : '');
            $message = $source . PHP_EOL . $message;
        }
        if ($message) waLog::log($message, $file);
        if ($data) {
            waLog::dump($data, $file);
        }
    }
    public static function sortArray($data, $field, $index = 'asc', $with_keys = false)
    {
        if (!$data) return $data;
        $result = array();
        foreach ($data as $key => $value) {
            $tmp_arr[$key] = $value[$field];
        }
        if ($index == 'asc') {
            asort($tmp_arr, SORT_LOCALE_STRING);
        } else {
            arsort($tmp_arr, SORT_LOCALE_STRING);
        }

        $keys = array_keys($tmp_arr);
        foreach ($keys as $key) {
            if ($with_keys) {
                $result[$key] = $data[$key];
            } else {
                $result[] = $data[$key];
            }
        }
        return $result;
    }
}
