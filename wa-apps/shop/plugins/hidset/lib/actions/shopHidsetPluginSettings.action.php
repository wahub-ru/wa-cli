<?php

class shopHidsetPluginSettingsAction extends waViewAction
{
    public function execute()
    {
        $plugin = wa()->getPlugin('hidset');
        $hsets = $plugin->hsets;
        $settings = wa('shop')->getConfig()->getOption(null);
        if (!isset($settings['sitemap_limit'])) {
            $value = 10000;
            if (defined('shopSitemapConfig::URL_FILE_LIMIT')) {
                $value = shopSitemapConfig::URL_FILE_LIMIT;
            }
            $settings['sitemap_limit'] = $value;
        }
        foreach ($settings as $set => $data) {
            if (!isset($hsets[$set])) unset($settings[$set]);
        }
        $dimensions = $plugin->getDimensions();
        $css_files = [
            'Shop-Script' => file_exists(wa()->getAppPath('css-legacy/shop.css')) ? wa()->getAppPath('css-legacy/shop.css') : wa()->getAppPath('css/shop.css'),
            'Webasyst' => wa()->getConfig()->getRootPath() . '/wa-content/css/wa/wa-1.3.css'
        ];
        $css = [];
        foreach ($css_files as $type => $file) {
            $data = $this->parseCss($file);
            asort($data);
            $css[$type] = array_values($data);
        }
        $v = wa()->getView();
        foreach (['actionButton'] as $component) {
            $file_template = wa()->whichUI('shop') === '1.3' ? ($component . '_old') : ($component);
            $template = wa()->getAppPath('plugins/hidset/templates/actions/settings/' . $file_template . '.vue');
            $this->view->assign($component, json_encode($v->fetch($template), 256));
        }
        $cli = new shopHidsetPluginCli();
        $cli->preExecute();
        $clis = $cli->getTasksInfo();
        unset ($cli);
        $tmpRepairs = include wa()->getAppPath('plugins/hidset/lib/config/data/repairs.php');
        foreach ($clis as $cli) {
            if ($cli['addon'] && $cli['handRun']) {
                $tmpRepairs[trim(str_replace('-task', '', $cli['command']))] = [
                    'description' => $cli['description'],
                    'addon' => true,
                    'formData' => $cli['formData']
                ];
            }
        }
        $repair_keys = array_keys($tmpRepairs);
        asort($repair_keys);
        $repairs = [];
        foreach ($repair_keys as $field) {
            $repairs[$field] = $tmpRepairs[$field];
        }
        $apps = wa()->getApps(true);

        $this->view->assign([
            'settings' => json_encode($settings),
            'sets' => json_encode($hsets),
            'plugin_sets' => json_encode($plugin->plugins),
            'dimensions' => json_encode($dimensions),
            'premium' => json_encode(waLicensing::check('shop/plugins/hidset')->isPremium()),
            'is_debug' => (waSystemConfig::isDebug() && file_exists(wa()->getAppPath('plugins/hidset/js/vendors/vue/vue.global.js'))),
            'repairs' => json_encode($repairs),
            'handlers' => json_encode($this->getHandlersInfo()),
            'app_url' => wa()->getAppUrl('shop'),
            'css' => json_encode($css),
            'clis' => json_encode($clis),
            'isCloud' => json_encode(isset($apps['cloud'])),
            'wa_path' => wa()->getConfig()->getRootPath(),
            'newUi' => wa()->whichUI('shop') !== '1.3'
        ]);
    }

    private function parseCss($file)
    {
        $css = file_get_contents($file);
        preg_match_all('/(?ims)([a-z0-9\s\.\:#_\-@,]+)\{([^\}]*)\}/', $css, $arr);
        $result = [];
        foreach ($arr[0] as $i => $x) {
            $selector = trim($arr[1][$i]);
            $selectors = explode(',', trim($selector));
            foreach ($selectors as $strSel) {
                if (strpos($strSel, '.icon16.') === 0) {
                    $result[] = trim(str_replace(['.icon16.', '.'], ' ', $strSel));
                }
            }
        }
        return $result;
    }

    private function getHandlersInfo()
    {
        $apps = wa()->getApps(true);
        $handlers = $items = [];
        foreach ($apps as $app_id => $app) {
            $wa = wa($app_id);
            foreach (glob(wa($app_id)->getAppPath('lib/handlers/*.php')) as $fullPath) {
                $aFullPath = explode('/', $fullPath);
                $path = array_pop($aFullPath);
                $aPath = explode('.', $path);
                if ($aPath[0] === 'wildcard') {
                    foreach (include($fullPath) as $file_handler) {
                        if (isset($apps[$file_handler['event_app_id']])) {
                            $items[$file_handler['event_app_id']][$file_handler['event']][] = ['id' => $app_id, 'type' => 'app', 'name' => $app['name']];
                        }
                    }
                } else {
                    if (isset($apps[$aPath[0]])) {
                        $items[$aPath[0]][$aPath[1]][] = ['id' => $app_id, 'type' => 'app', 'name' => $app['name']];
                    }
                }
            }
            foreach ($wa->getConfig()->getPlugins() as $plugin_id => $plugin) {
                foreach (ifset($plugin['handlers'], []) as $handler => $method) {
                    if ($handler === '*') {
                        foreach ($method as $data) {
                            $items[$data['event_app_id']][$data['event']][] = ['id' => $plugin_id, 'type' => 'plugin', 'name' => $plugin['name']];
                        }
                    } else $items[$app_id][$handler][] = ['id' => $plugin_id, 'type' => 'plugin', 'name' => $plugin['name']];
                }
            }
        }
        foreach ($items as $app_id => $app_handlers_data) {
            $app_handlers = [];
            foreach ($app_handlers_data as $handler => $datum) {
                $app_handlers[] = [
                    'handler' => $handler,
                    'items' => shopHidsetPlugin::sortArray($datum, 'name')
                ];
            }
            $app_handlers = shopHidsetPlugin::sortArray($app_handlers, 'handler');
            $handlers[] = [
                'app_id' => $app_id,
                'name' => ifset($apps[$app_id]['name'], 'Не удалось определить'),
                'handlers' => $app_handlers
            ];
        }
        $idx = array_search('shop', array_column($handlers, 'app_id'));
        if ($idx !== false) {
            $shop = $handlers[$idx];
            unset($handlers[$idx]);
            array_unshift($handlers, $shop);
        }
        return array_values($handlers);
    }
}