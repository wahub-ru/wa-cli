<?php
/*
 * @link https://warslab.ru/
 * @author waResearchLab
 * @Copyright (c) 2023 waResearchLab
 */
foreach (['vue.js', 'vue.min.js'] as $file) {
    try {
        waFiles::delete(wa()->getAppPath('plugins/hidset/js/' . $file));
    } catch (waException $e) {
    }
}