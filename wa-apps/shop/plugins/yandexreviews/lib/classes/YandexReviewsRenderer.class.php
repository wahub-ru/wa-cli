<?php

class YandexReviewsRenderer
{
    /** @var shopPlugin */
    private $plugin;

    public function __construct(shopPlugin $plugin)
    {
        $this->plugin = $plugin;
    }

    /**
     * Параметры метода намеренно игнорируются — берём настройки плагина,
     * чтобы исключить расхождения между первым рендером и AJAX-подгрузкой.
     */
    public function render($view, $limit, $hide_low, $sort = 'date_desc')
    {
        $settings = $this->plugin->getSettingsForStorefront();

        // ====== ВСЁ БЕРЁМ ИЗ НАСТРОЕК ======
        $view_mode = (!empty($settings['view_mode']) && in_array($settings['view_mode'], ['tiles','list'], true))
            ? $settings['view_mode'] : 'tiles';

        $initial_limit = (int)($settings['initial_limit'] ?? 8);
        if ($initial_limit < 1)  { $initial_limit = 1; }
        if ($initial_limit > 50) { $initial_limit = 50; }

        $hide_low = !empty($settings['hide_low_ratings']);
        $sort = 'date_desc';
        // ===================================

        $company_url = (string)($settings['company_url'] ?? '');
        $yid = shopYandexreviewsPlugin::parseCompanyId($company_url);
        if (!$yid) {
            return '';
        }

        $cm = new shopYandexreviewsCompanyModel();
        $rm = new shopYandexreviewsReviewModel();

        $company = $cm->getByYandexId($yid);
        if (!$company) {
            return '';
        }

        $offset  = 0;
        $items   = $rm->getPaged($company['id'], $offset, $initial_limit, (bool)$hide_low, $sort);
        $total   = $rm->countAvailable($company['id'], (bool)$hide_low);
        $hasMore = ($offset + $initial_limit) < $total;

        // Подставим публичный URL аватарки (локальная — приоритет) + фильтр битых внешних ссылок
        $avatar     = new YandexReviewsAvatarService();
        $photos     = new YandexReviewsPhotoService();
        $company_id = (int)$company['id'];

        $allow_yapic = static function (string $u): ?string {
            // отбраковываем маскированные/обрезанные get-yapic ID
            if ($u === '' || strpos($u, '/enc-') !== false) return null;
            if (!preg_match('~^https?://avatars\.mds\.yandex\.net/get-yapic/\d+/([A-Za-z0-9_-]{1,64})(?:/|$)~', $u, $m)) return null;
            if ($m[1] === '' || substr($m[1], -1) === '-') return null;
            return $u;
        };

        foreach ($items as &$it) {
            $it['avatar_public'] = null;
            $it['photos'] = [];

            if (!empty($it['author_avatar_local'])) {
                // локальный кэш — всегда приоритет
                $it['avatar_public'] = $avatar->getPublicUrl($company_id, $it['author_avatar_local']);
            } elseif (!empty($it['author_avatar'])) {
                if ($url = $allow_yapic((string)$it['author_avatar'])) {
                    $it['avatar_public'] = $url;
                }
            }

            // финальная страховка: если в БД уже лежит enc- – не выводим картинку
            if ($it['avatar_public'] && strpos($it['avatar_public'], '/enc-') !== false) {
                $it['avatar_public'] = null;
            }

            $photo_items = $photos->normalizePhotos($it['photos_json'] ?? '');
            if ($photo_items) {
                $it['photos'] = $photos->buildPublicUrls($photo_items);
            }
        }
        unset($it);

        // AJAX URL на наш роут
        $storefront = rtrim(wa()->getRouteUrl('shop/frontend', [], true), '/');
        $ajax = $storefront . '/yandexreviews/';

        // Кнопка «Оставить отзыв»
        $show_btn    = !empty($settings['show_review_button']);
        $reviews_url = shopYandexreviewsPlugin::makeReviewsUrl($company_url);

        // Кастомизация кнопки (все значения берём из настроек)
        $btn_text = trim((string)($settings['review_button_text'] ?? ''));
        if ($btn_text === '') {
            $btn_text = 'Оставить отзыв на Яндекс Картах';
        }

        // Разрешаем только безопасные HEX-цвета (input type=color)
        $color_re = '/^#(?:[0-9a-fA-F]{3}|[0-9a-fA-F]{6})$/';

        $btn_style_parts = [];
        $bg = trim((string)($settings['review_button_bg_color'] ?? ''));
        if ($bg !== '' && preg_match($color_re, $bg)) {
            // Не задаём background-color/ color напрямую (inline мешает :hover)
            // Передаём через CSS-переменные
            $btn_style_parts[] = '--yreviews-btn-bg:' . $bg;
            $btn_style_parts[] = '--yreviews-btn-border:0';
        }

        $tx = trim((string)($settings['review_button_text_color'] ?? ''));
        if ($tx !== '' && preg_match($color_re, $tx)) {
            $btn_style_parts[] = '--yreviews-btn-color:' . $tx;
        }

        $radius_raw = trim((string)($settings['review_button_radius'] ?? ''));
        if ($radius_raw !== '') {
            $r = (int)$radius_raw;
            if ($r < 0) { $r = 0; }
            if ($r > 60) { $r = 60; }
            $btn_style_parts[] = '--yreviews-btn-radius:' . $r . 'px';
        }

        // Hover-цвета: тоже через CSS-переменные
        $btn_id = 'yreviews-review-btn-' . (int)$company['id'];
        $bg_h = trim((string)($settings['review_button_bg_color_hover'] ?? ''));
        $tx_h = trim((string)($settings['review_button_text_color_hover'] ?? ''));

        if ($bg_h !== '' && preg_match($color_re, $bg_h)) {
            $btn_style_parts[] = '--yreviews-btn-bg-hover:' . $bg_h;
        }
        if ($tx_h !== '' && preg_match($color_re, $tx_h)) {
            $btn_style_parts[] = '--yreviews-btn-color-hover:' . $tx_h;
        }

        $btn_style = $btn_style_parts ? implode('; ', $btn_style_parts) . ';' : '';

        // CSS, который применяет переменные (нужно, чтобы :hover работал поверх inline)
        $btn_hover_css = '';
        if ($btn_style !== '') {
            $btn_hover_css = '#'.$btn_id.'{'
                .'background-color:var(--yreviews-btn-bg);'
                .'color:var(--yreviews-btn-color);'
                .'border-radius:var(--yreviews-btn-radius);'
                .'border:var(--yreviews-btn-border);'
                .'transition:background-color .15s ease, color .15s ease, opacity .15s ease;'
                .'}'
                .'#'.$btn_id.':hover{'
                .'background-color:var(--yreviews-btn-bg-hover, var(--yreviews-btn-bg));'
                .'color:var(--yreviews-btn-color-hover, var(--yreviews-btn-color));'
                .'}';
        }

        // Пути к партиалам
        $tpl_items_tiles = wa()->getAppPath('plugins/yandexreviews/templates/reviews_items.tiles.html', 'shop');
        $tpl_items_list  = wa()->getAppPath('plugins/yandexreviews/templates/reviews_items.list.html',  'shop');

        // ====== СТАТИКА (CSS/JS) — абсолютные URL, чтобы работало и на страницах Site ======
        $static_url = wa()->getAppStaticUrl('shop', true); // абсолютный URL к /wa-apps/shop/...
        $ver        = method_exists($this->plugin, 'getVersion') ? ('?v='.$this->plugin->getVersion()) : '';

        // CSS
        $css_rel = 'plugins/yandexreviews/css/yandexreviews.css';
        $css_abs = wa()->getAppPath($css_rel, 'shop');
        if (file_exists($css_abs)) {
            wa()->getResponse()->addCss($static_url . $css_rel . $ver);
        }

        // (необязательно) JS
        $js_rel  = 'plugins/yandexreviews/js/frontend.js';
        $js_abs  = wa()->getAppPath($js_rel, 'shop');
        if (file_exists($js_abs)) {
            wa()->getResponse()->addJs($static_url . $js_rel . $ver);
        }
        // =====================================================================================

        $v = wa()->getView();
        $v->assign([
            'view_mode'            => $view_mode,
            'company'              => $company,
            'company_url'          => $company_url,
            'reviews'              => $items,
            'offset'               => $offset,
            'limit'                => $initial_limit,
            'total'                => $total,
            'has_more'             => $hasMore,
            'hide_low'             => $hide_low,
            'sort'                 => $sort,
            'show_photos'          => false,
            'ajax_url'             => $ajax,
            'show_review_btn'      => $show_btn,
            'review_btn_url'       => $reviews_url,
            'review_btn_text'      => $btn_text,
            'review_btn_style'     => $btn_style,
            'review_btn_id'        => $btn_id,
            'review_btn_hover_css' => $btn_hover_css,
            'tpl_items_tiles'      => $tpl_items_tiles,
            'tpl_items_list'       => $tpl_items_list,
        ]);

        return $v->fetch(wa()->getAppPath('plugins/yandexreviews/templates/reviews.html', 'shop'));
    }
}
