<?php

class shopYandexreviewsPluginSettingsAction extends waViewAction
{
    public function execute()
    {
        /** @var shopYandexreviewsPlugin $plugin */
        $plugin = wa('shop')->getPlugin('yandexreviews');
        $settings = $plugin ? $plugin->getSettings() : array();

        $storefront = waRequest::get('storefront', '', waRequest::TYPE_STRING_TRIM);
        $storefront = shopYandexreviewsPlugin::normalizeStorefront($storefront);
        $storefronts = shopYandexreviewsPlugin::getStorefrontOptions();
        if ($storefront !== '' && !array_key_exists($storefront, $storefronts)) {
            $storefront = '';
        }

        if ($storefront === '') {
            $defaults = shopYandexreviewsPlugin::getSettingsDefaults(false);
            $settings = array_merge($defaults, (array)$settings);
        } else {
            $defaults = shopYandexreviewsPlugin::getSettingsDefaults(true);
            $scoped = shopYandexreviewsPlugin::getStorefrontSettingsRaw($storefront);
            $settings = array_merge($defaults, $scoped);
        }

        $settings_fetch_url = '?plugin=yandexreviews&module=settings&action=fetch';
        $settings_reset_url = '?plugin=yandexreviews&module=settings&action=resetStorefronts';
        $settings_delete_reviews_url = '?plugin=yandexreviews&module=settings&action=deleteReviews';
        $storefronts_custom = shopYandexreviewsPlugin::getStorefrontsWithOverrides();
        $has_storefront_overrides = !empty($storefronts_custom);
        $reviews_total = (new shopYandexreviewsReviewModel())->countAll();

        $this->view->assign('settings', $settings);
        $this->view->assign('current_storefront', $storefront);
        $this->view->assign('storefronts', $storefronts);
        $this->view->assign('settings_fetch_url', $settings_fetch_url);
        $this->view->assign('settings_reset_url', $settings_reset_url);
        $this->view->assign('settings_delete_reviews_url', $settings_delete_reviews_url);
        $this->view->assign('storefronts_custom', $storefronts_custom);
        $this->view->assign('has_storefront_overrides', $has_storefront_overrides);
        $this->view->assign('reviews_total', $reviews_total);
        
// Путь до cli.php
$root = rtrim(wa()->getConfig()->getRootPath(), '/\\');
$cli  = $root . '/cli.php';

// НУЖНЫЕ ВАМ КОМАНДЫ (без редиректов и без кавычек)
$cron_import  = 'php ' . $cli . ' shop yandexreviewsPluginImport';
$cron_avatars = 'php ' . $cli . ' shop yandexreviewsPluginAvatars';

// в шаблон
$this->view->assign('cron_import_cmd',  $cron_import);
$this->view->assign('cron_avatars_cmd', $cron_avatars);

// -----------------------------------------


        // отрисовываем ваш шаблон настроек
        $this->setTemplate(wa()->getAppPath('plugins/yandexreviews/templates/settings.html', 'shop'));
    }
}
