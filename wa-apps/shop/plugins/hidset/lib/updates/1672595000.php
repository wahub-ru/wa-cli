<?php
/*
 * @link https://warslab.ru/
 * @author waResearchLab
 * @Copyright (c) 2023 waResearchLab
 */
$files = [
    'templates/actions/settings/setting.tr.html',
    'lib/actions/shopHidsetPluginBackendSave.controller.php'
];
foreach ($files as $file) {
    try {
        $path = wa()->getAppPath('plugins/hidset/') . $file;
        if (file_exists($path)) {
            waFiles::delete($path);
        }
    } catch (waException $e) {
        waLog::log($e->getMessage());
    }
}