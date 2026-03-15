<?php

class shopTgconsultPluginSaveSettingsController extends waController
{
    public function execute()
    {
        // Права только для админа магазина
        if (!wa()->getUser()->isAdmin('shop')) {
            throw new waRightsException('Access denied');
        }

        // Простая CSRF-проверка (токен берем из скрытого input на форме)
        $posted_csrf = waRequest::post('csrf', '', 'string');
        if (!$posted_csrf || $posted_csrf !== wa()->getStorage()->read('csrf')) {
            throw new waException('Invalid CSRF token', 403);
        }

        $plugin_id = 'tgconsult';
        $namespace = wa()->getApp().'_'.$plugin_id; // shop_tgconsult

        /** @var shopPlugin $plugin */
        $plugin   = wa('shop')->getPlugin($plugin_id);
        $settings = waRequest::post($namespace, [], waRequest::TYPE_ARRAY);

        // Нормализация чекбокса
        $settings['enabled'] = !empty($settings['enabled']) ? 1 : 0;
        $settings['hide_button'] = !empty($settings['hide_button']) ? 1 : 0;

        // Сохраняем
        $plugin->saveSettings($settings);

        // Куда возвращаться
        $return_url = waRequest::post('return_url', wa()->getAppUrl('shop').'?plugin=tgconsult&action=settings', 'string');

        $this->redirect($return_url);
    }
}
