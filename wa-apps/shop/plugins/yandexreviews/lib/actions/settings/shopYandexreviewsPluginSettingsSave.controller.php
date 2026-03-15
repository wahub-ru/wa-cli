<?php

class shopYandexreviewsPluginSettingsSaveController extends waJsonController
{
    public function execute()
    {
        /** @var shopYandexreviewsPlugin $plugin */
        $plugin = wa('shop')->getPlugin('yandexreviews');

        // 1) Получаем вложенные поля settings[...] (FormData POST)
        $in = waRequest::post('settings', [], waRequest::TYPE_ARRAY_TRIM);

        // Фоллбэк: если шлют JSON
        if (!$in) {
            $raw = file_get_contents('php://input');
            if ($raw) {
                $json = json_decode($raw, true);
                if (is_array($json) && !empty($json['settings']) && is_array($json['settings'])) {
                    $in = $json['settings'];
                }
            }
        }

        // 2) Нормализуем/валидируем
        $view_mode = (isset($in['view_mode']) && in_array($in['view_mode'], ['tiles','list'], true))
            ? $in['view_mode'] : 'tiles';

        $storefront = waRequest::post('storefront', '', waRequest::TYPE_STRING_TRIM);
        $storefront = shopYandexreviewsPlugin::normalizeStorefront($storefront);
        $storefronts = shopYandexreviewsPlugin::getStorefrontOptions();
        if ($storefront !== '' && !array_key_exists($storefront, $storefronts)) {
            $storefront = '';
        }

        // --- Кастомизация кнопки ---
        $btn_text = trim((string)($in['review_button_text'] ?? ''));
        if ($btn_text === '') {
            $btn_text = 'Оставить отзыв на Яндекс Картах';
        }
        // ограничим длину (защита от случайных вставок)
        if (mb_strlen($btn_text, 'UTF-8') > 120) {
            $btn_text = mb_substr($btn_text, 0, 120, 'UTF-8');
        }

        $color_re = '/^#(?:[0-9a-fA-F]{3}|[0-9a-fA-F]{6})$/';

        // INPUT type=color всегда отдаёт значение, поэтому используем чекбоксы «использовать по умолчанию»
        $btn_bg_is_default       = !empty($in['review_button_bg_color__default']);
        $btn_tx_is_default       = !empty($in['review_button_text_color__default']);
        $btn_bg_hover_is_default = !empty($in['review_button_bg_color_hover__default']);
        $btn_tx_hover_is_default = !empty($in['review_button_text_color_hover__default']);

        $btn_bg = $btn_bg_is_default ? '' : trim((string)($in['review_button_bg_color'] ?? ''));
        if ($btn_bg !== '' && !preg_match($color_re, $btn_bg)) {
            $btn_bg = '';
        }

        $btn_color = $btn_tx_is_default ? '' : trim((string)($in['review_button_text_color'] ?? ''));
        if ($btn_color !== '' && !preg_match($color_re, $btn_color)) {
            $btn_color = '';
        }

        $btn_bg_hover = $btn_bg_hover_is_default ? '' : trim((string)($in['review_button_bg_color_hover'] ?? ''));
        if ($btn_bg_hover !== '' && !preg_match($color_re, $btn_bg_hover)) {
            $btn_bg_hover = '';
        }

        $btn_color_hover = $btn_tx_hover_is_default ? '' : trim((string)($in['review_button_text_color_hover'] ?? ''));
        if ($btn_color_hover !== '' && !preg_match($color_re, $btn_color_hover)) {
            $btn_color_hover = '';
        }

        $btn_radius_raw = trim((string)($in['review_button_radius'] ?? ''));
        if ($btn_radius_raw === '') {
            $btn_radius = '';
        } else {
            $btn_radius = (int)$btn_radius_raw;
            if ($btn_radius < 0)  { $btn_radius = 0; }
            if ($btn_radius > 60) { $btn_radius = 60; }
        }

        $data = [
            'enabled'            => !empty($in['enabled']) ? 1 : 0,
            'company_url'        => (string)($in['company_url'] ?? ''),
            'view_mode'          => $view_mode,
            'initial_limit'      => max(1, min(50,  (int)($in['initial_limit'] ?? 8))),
            'cron_batch_limit'   => max(1, min(200, (int)($in['cron_batch_limit'] ?? 30))),
            'hide_low_ratings'   => !empty($in['hide_low_ratings']) ? 1 : 0,
            'show_review_button' => !empty($in['show_review_button']) ? 1 : 0,

            // кастомизация кнопки
            'review_button_text'             => $btn_text,
            'review_button_bg_color'         => $btn_bg,
            'review_button_text_color'       => $btn_color,
            'review_button_bg_color_hover'   => $btn_bg_hover,
            'review_button_text_color_hover' => $btn_color_hover,
            'review_button_radius'           => $btn_radius,
        ];

        // 3) Сохраняем настройки в wa_app_settings
        $settings_key = shopYandexreviewsPlugin::getSettingsKeyForStorefront($storefront);
        $settings_model = new waAppSettingsModel();
        $base_settings_before = $settings_model->get(['shop', 'yandexreviews']);
        if (!is_array($base_settings_before)) {
            $base_settings_before = [];
        }
        $old_urls = shopYandexreviewsPlugin::getCompanyUrlsForImport($base_settings_before);
        foreach ($data as $name => $value) {
            $settings_model->set($settings_key, $name, is_array($value) ? json_encode($value) : $value);
        }

        // 4) Обновляем список компаний
        try {
            if ($data['enabled']) {
                $cm  = new shopYandexreviewsCompanyModel();
                $now = date('Y-m-d H:i:s');

                $urls = shopYandexreviewsPlugin::getCompanyUrlsForImport($plugin->getSettings());

                foreach ($urls as $url) {
                    $yid = shopYandexreviewsPlugin::parseCompanyId($url);
                    if (!$yid) {
                        continue;
                    }

                    $company = $cm->getByYandexId($yid);
                    if ($company) {
                        $cm->updateById($company['id'], [
                            'yandex_company_id'   => (string)$yid,
                            'url'                 => $url,
                            'last_fetch_datetime' => $now,
                        ]);
                    } else {
                        $cm->insert([
                            'yandex_company_id'   => (string)$yid,
                            'name'                => null,
                            'rating'              => null,
                            'reviews_total'       => null,
                            'url'                 => $url,
                            'last_fetch_datetime' => $now,
                            'create_datetime'     => $now,
                        ]);
                    }
                }
            }
        } catch (Exception $e) {
            // в лог, но сохранению настроек не мешаем
            waLog::log('Yandexreviews settings company update error: '.$e->getMessage(), 'yandexreviews.log');
        }

        // 5) Удаляем компании, ссылки на которые были очищены в настройках
        $base_settings_after = $settings_model->get(['shop', 'yandexreviews']);
        if (!is_array($base_settings_after)) {
            $base_settings_after = [];
        }
        $new_urls = shopYandexreviewsPlugin::getCompanyUrlsForImport($base_settings_after);
        $removed_urls = array_diff($old_urls, $new_urls);
        if ($removed_urls) {
            $used_yids = [];
            foreach ($new_urls as $url) {
                $yid = shopYandexreviewsPlugin::parseCompanyId($url);
                if ($yid) {
                    $used_yids[$yid] = true;
                }
            }
            $cm = new shopYandexreviewsCompanyModel();
            foreach ($removed_urls as $url) {
                $yid = shopYandexreviewsPlugin::parseCompanyId($url);
                if ($yid && !isset($used_yids[$yid])) {
                    $cm->deleteByField('yandex_company_id', (string)$yid);
                } elseif (!$yid) {
                    $cm->deleteByField('url', $url);
                }
            }
        }

        // 6) Ответ
        $this->response = [
            'status' => 'ok',
            'saved'  => shopYandexreviewsPlugin::applyStorefrontSettings($plugin->getSettings(), $storefront),
        ];
    }
}
