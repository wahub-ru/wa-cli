<?php

class shopYandexreviewsPlugin extends shopPlugin
{
    public static function getSettingsDefaults(bool $for_storefront = false): array
    {
        $defaults = array(
            'enabled'            => 1,
            'company_url'        => '',
            'view_mode'          => 'tiles',
            'initial_limit'      => 8,
            'cron_batch_limit'   => 30,
            'hide_low_ratings'   => 0,
            'show_review_button' => 1,

            // кастомизация кнопки «Оставить отзыв»
            'review_button_text'             => 'Оставить отзыв на Яндекс Картах',
            'review_button_bg_color'         => '',
            'review_button_text_color'       => '',
            'review_button_bg_color_hover'   => '',
            'review_button_text_color_hover' => '',
            'review_button_radius'           => '',
        );

        if ($for_storefront) {
            $defaults['enabled'] = 0;
            $defaults['company_url'] = '';
        }

        return $defaults;
    }

    /**
     * Всегда берём параметры из настроек.
     * Вызов: {$wa->shop->yandexreviewsPlugin->reviews()}
     */
    public function reviews($view = null, $limit = null, $hide_low = null, $sort = null)
    {
        $s = $this->getSettingsForStorefront();

        $view_mode = (!empty($s['view_mode']) && in_array($s['view_mode'], ['tiles','list'], true))
            ? $s['view_mode'] : 'tiles';

        $initial_limit = (int)($s['initial_limit'] ?? 8);
        if ($initial_limit < 1)  { $initial_limit = 1; }
        if ($initial_limit > 50) { $initial_limit = 50; }

        $hide_low = !empty($s['hide_low_ratings']);
        $sort = 'date_desc';

        $renderer = new YandexReviewsRenderer($this);
        return $renderer->render($view_mode, $initial_limit, $hide_low, $sort);
    }

    public static function normalizeStorefront($storefront)
    {
        $storefront = trim((string)$storefront);
        if ($storefront === '') {
            return '';
        }
        $storefront = preg_replace('~^https?://~i', '', $storefront);
        $storefront = rtrim($storefront);
        $storefront = rtrim($storefront, '/');
        $storefront = rtrim($storefront, '*');
        $storefront = rtrim($storefront, '/');
        if ($storefront === '') {
            return '';
        }
        return $storefront.'/*';
    }

    public static function getCurrentStorefront(): string
    {
        $routing = wa()->getRouting();
        $domain = (string)$routing->getDomain();
        $route = (array)$routing->getRoute();
        $url = (string)($route['url'] ?? '');

        $storefront = $domain;
        if ($url !== '') {
            $storefront .= '/'.$url;
        }

        return self::normalizeStorefront($storefront);
    }

    public static function getSettingsKeyForStorefront(?string $storefront = null): array
    {
        $storefront = self::normalizeStorefront($storefront ?? '');
        $key = 'yandexreviews';
        if ($storefront !== '') {
            $key .= '.'.$storefront;
        }
        return ['shop', $key];
    }

    public static function getStorefrontOptions(): array
    {
        if (!class_exists('shopHelper')) {
            wa('shop');
        }
        if (!method_exists('shopHelper', 'getStorefronts')) {
            return [];
        }

        $storefronts = shopHelper::getStorefronts(true);
        $options = [];

        foreach ($storefronts as $storefront) {
            $raw_url = (string)($storefront['url'] ?? '');
            if ($raw_url === '') {
                continue;
            }

            $value = self::normalizeStorefront($raw_url);
            if ($value === '') {
                continue;
            }

            $label_raw = (string)($storefront['url_decoded'] ?? $raw_url);
            $label = self::normalizeStorefront($label_raw);

            $options[$value] = $label !== '' ? $label : $value;
        }

        ksort($options);

        return $options;
    }

    public static function applyStorefrontSettings(array $settings, ?string $storefront = null): array
    {
        $storefront = self::normalizeStorefront($storefront ?? self::getCurrentStorefront());
        if ($storefront === '') {
            return $settings;
        }

        $model = new waAppSettingsModel();
        $scoped = $model->get(self::getSettingsKeyForStorefront($storefront));
        if (is_array($scoped) && $scoped) {
            $settings = array_merge($settings, $scoped);
        }

        return $settings;
    }

    public static function getStorefrontSettingsRaw(?string $storefront = null): array
    {
        $storefront = self::normalizeStorefront($storefront ?? '');
        if ($storefront === '') {
            return [];
        }

        $model = new waAppSettingsModel();
        $scoped = $model->get(self::getSettingsKeyForStorefront($storefront));

        return is_array($scoped) ? $scoped : [];
    }

    public static function getStorefrontsWithOverrides(): array
    {
        $storefronts = self::getStorefrontOptions();
        if (!$storefronts) {
            return [];
        }

        $model = new waAppSettingsModel();
        $overrides = [];

        foreach (array_keys($storefronts) as $storefront) {
            $scoped = $model->get(self::getSettingsKeyForStorefront($storefront));
            if (is_array($scoped) && $scoped) {
                $overrides[$storefront] = true;
            }
        }

        return $overrides;
    }

    public function getSettingsForStorefront(?string $storefront = null): array
    {
        return self::applyStorefrontSettings($this->getSettings(), $storefront);
    }

    public static function getCompanyUrlForStorefront(array $settings, ?string $storefront = null): string
    {
        $settings = self::applyStorefrontSettings($settings, $storefront);
        return (string)($settings['company_url'] ?? '');
    }

    public static function getCompanyUrlsForImport(array $settings): array
    {
        $urls = [];
        $single = trim((string)($settings['company_url'] ?? ''));
        if ($single !== '') {
            $urls[] = $single;
        }

        $storefronts = self::getStorefrontOptions();
        if ($storefronts) {
            $model = new waAppSettingsModel();
            foreach (array_keys($storefronts) as $storefront) {
                $scoped = $model->get(self::getSettingsKeyForStorefront($storefront));
                $url = trim((string)($scoped['company_url'] ?? ''));
                if ($url !== '') {
                    $urls[] = $url;
                }
            }
        }

        $unique = [];
        foreach ($urls as $url) {
            $url = trim((string)$url);
            if ($url === '') {
                continue;
            }
            $unique[$url] = true;
        }

        return array_keys($unique);
    }

    public static function parseCompanyId($url)
    {
        $u = (string)$url;
        if (preg_match('~/org/[^/]+/(\d{6,})~', $u, $m)) return $m[1];
        if (preg_match('~(\d{6,})~', $u, $m)) return $m[1];
        return null;
    }

    public static function makeReviewsUrl($company_url)
    {
        $u = trim((string)$company_url);
        if ($u === '') return '';
        if (strpos($u, '/reviews') === false) $u = rtrim($u, '/').'/reviews/';
        return $u;
    }

public function frontendHead() { return ''; }
}
