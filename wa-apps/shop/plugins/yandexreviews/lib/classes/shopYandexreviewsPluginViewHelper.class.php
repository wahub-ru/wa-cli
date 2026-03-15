<?php

class shopYandexreviewsPluginViewHelper extends waPluginViewHelper
{
    /**
     * Вызов в шаблоне/странице магазина:
     *   {$wa->shop->yandexreviewsPlugin->reviews("tiles", 8)}                // hide — из настроек
     *   {$wa->shop->yandexreviewsPlugin->reviews("tiles", 8, 1)}             // принудительно скрывать 1–3★
     *   {$wa->shop->yandexreviewsPlugin->reviews("tiles", 8, null, "rating_desc")}
     */
    public function reviews($view = 'tiles', $limit = 8, $hide = null, $sort = 'date_desc')
    {
        /** @var shopYandexreviewsPlugin|null $plugin */
        $plugin = $this->plugin();
        if (!$plugin) return '';

        $settings = $plugin->getSettings();
        if ($hide === null) {
            $hide = !empty($settings['hide_low_ratings']);
        }

        $renderer = new YandexReviewsRenderer($plugin);
        return (string) $renderer->render(
            in_array($view, ['tiles','list'], true) ? $view : 'tiles',
            max(1, (int)$limit),
            (bool)$hide,
            $sort
        );
    }
}
