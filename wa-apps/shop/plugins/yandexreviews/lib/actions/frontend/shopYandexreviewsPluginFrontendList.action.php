<?php

class shopYandexreviewsPluginFrontendListAction extends waViewAction
{
    public function execute()
    {
        $plugin   = wa('shop')->getPlugin('yandexreviews');
        $settings = $plugin->getSettingsForStorefront();

        // Настройки и входные параметры
        $company_url = (string)($settings['company_url'] ?? '');
        $yid = shopYandexreviewsPlugin::parseCompanyId($company_url);
        if (!$yid) {
            $this->getResponse()->addHeader('X-Total', '0');
            $this->view->assign('reviews', []);
            $this->setTemplate('string:');
            return;
        }

        $view  = waRequest::request('view', (string)($settings['view_mode'] ?? 'tiles'), waRequest::TYPE_STRING_TRIM);
        if (!in_array($view, ['tiles', 'list'], true)) {
            $view = 'tiles';
        }

        $limit  = max(1, min(50, (int)waRequest::request('limit', (int)($settings['initial_limit'] ?? 8))));
        $offset = max(0, (int)waRequest::request('offset', 0));
        $hide   = (bool)waRequest::request('hide', !empty($settings['hide_low_ratings']));
        $sort   = waRequest::request('sort', 'date_desc', waRequest::TYPE_STRING_TRIM);

        // Модели
        $cm = new shopYandexreviewsCompanyModel();
        $rm = new shopYandexreviewsReviewModel();

        $company = $cm->getByYandexId($yid);
        if (!$company) {
            $this->getResponse()->addHeader('X-Total', '0');
            $this->view->assign('reviews', []);
            $this->setTemplate('string:');
            return;
        }

        // Получаем порцию отзывов
        $items = $rm->getPaged($company['id'], $offset, $limit, $hide, $sort);
        $total = $rm->countAvailable($company['id'], $hide);

        // === ВАЖНО: подсчёт avatar_public для AJAX ===
        $avatar = new YandexReviewsAvatarService();
        $photos = new YandexReviewsPhotoService();
        foreach ($items as &$it) {
            if (!empty($it['author_avatar_local'])) {
                $it['avatar_public'] = $avatar->getPublicUrl((int)$company['id'], $it['author_avatar_local']);
            } elseif (!empty($it['author_avatar'])) {
                $it['avatar_public'] = $it['author_avatar'];
            } else {
                $it['avatar_public'] = null;
            }

            $photo_items = $photos->normalizePhotos($it['photos_json'] ?? '');
            $it['photos'] = $photo_items ? $photos->buildPublicUrls($photo_items) : [];
        }
        unset($it);
        // ============================================

        // Заголовок для клиента (сколько всего)
        $this->getResponse()->addHeader('X-Total', (string)$total);

        // Выбираем нужный партиал
        $tpl_items_tiles = wa()->getAppPath('plugins/yandexreviews/templates/reviews_items.tiles.html', 'shop');
        $tpl_items_list  = wa()->getAppPath('plugins/yandexreviews/templates/reviews_items.list.html',  'shop');
        $tpl = ($view === 'list') ? $tpl_items_list : $tpl_items_tiles;

        $this->view->assign([
            'company'     => $company,
            'company_url' => $company_url,
            'reviews'     => $items,
            'offset'      => $offset,
            'limit'       => $limit,
            'total'       => $total,
            'hide_low'    => (int)$hide,
            'sort'        => $sort,
        ]);

        $this->setTemplate($tpl);
    }
}
