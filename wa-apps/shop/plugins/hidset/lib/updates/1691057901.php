<?php
/*
 * @link https://warslab.ru/
 * @author waResearchLab
 * @Copyright (c) 2023 waResearchLab
 */
foreach (['js/vue.js', 'js/vue.min.js', 'js/vendors/vue/vue.global.js'] as $file_name) {
    $file = wa()->getAppPath('plugins/hidset/' . $file_name);
    if (file_exists($file)) {
        try {
            waFiles::delete($file);
        } catch (waException $e) {
        }
    }
}