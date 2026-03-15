<?php

class shopYandexreviewsPluginSettingsFetchController extends waJsonController
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

        $this->response = [
            'settings' => $settings,
        ];
    }
}
