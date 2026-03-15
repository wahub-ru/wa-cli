<?php

class shopMobiletoolbarPluginViewHelper extends waPluginViewHelper
{
    public function panel($options = array())
    {
        /** @var shopMobiletoolbarPlugin $plugin */
        $plugin = $this->plugin;
        if (!$plugin) { return ''; }

        $s = (array) $plugin->getSettings();
        if (empty($s['enabled'])) { return ''; }

        $buttons = $this->prepareButtons($s);
        if (!$buttons) { return ''; }
        
        $use_searchpro = !empty($s['searchpro_enabled']) && class_exists('shopSearchproPluginViewHelper');

        // Данные в шаблон
        $v = wa()->getView();
        $v->assign(array(
            'mobiletoolbar_buttons'      => $buttons,
            'mobiletoolbar_catalog_tree' => $this->hasType($buttons, 'catalog') ? $this->getCatalogTree() : array(),
            'mobiletoolbar_has_search'   => $this->hasType($buttons, 'search'),
            'mobiletoolbar_search_url'   => wa()->getRouteUrl('shop/frontend/search'),
            'mobiletoolbar_use_searchpro'  => $use_searchpro,
        ));

        // HTML панели
        $tpl  = wa()->getAppPath('plugins/mobiletoolbar/templates/frontend_panel.html', 'shop');
        $html = is_readable($tpl) ? $v->fetch($tpl) : '';

        // CSS/JS инлайном
        $css_file = wa()->getAppPath('plugins/mobiletoolbar/css/mobiletoolbar.css', 'shop');
        $js_file  = wa()->getAppPath('plugins/mobiletoolbar/js/mobiletoolbar.js',  'shop');
        $css = is_readable($css_file) ? file_get_contents($css_file) : '';
        $js  = is_readable($js_file)  ? file_get_contents($js_file)  : '';

        return ($css ? "<style id=\"shop-mobiletoolbar-styles\">".$css."</style>\n" : '')
             . $html
             . ($js  ? "\n<script id=\"shop-mobiletoolbar-script\">".$js."</script>\n" : '');
    }

    /* -------- helpers -------- */

    private function hasType(array $buttons, $type)
    {
        foreach ($buttons as $b) {
            if ($b['type'] === $type) return true;
        }
        return false;
    }

private function prepareButtons(array $settings)
{
    $map = array(
        'home'      => 'Главная',
        'catalog'   => 'Каталог',
        'cart'      => 'Корзина',
        'favorites' => 'Избранное',
        'compare'   => 'Сравнение',
        'search'    => 'Поиск',
        'account'   => 'Кабинет',
    );

    // Текущий URL
    $uri   = wa()->getConfig()->getRequestUrl(false, true);
    $path  = (string) parse_url($uri, PHP_URL_PATH);
    $query = (string) parse_url($uri, PHP_URL_QUERY);
    $qs    = array();
    if ($query) { parse_str($query, $qs); }

    // Активная вкладка
    $active_type = 'home';
    if (strpos($path, '/cart') !== false) {
        $active_type = 'cart';
    } elseif (strpos($path, '/compare') !== false) {
        $active_type = 'compare';
    } elseif (strpos($path, '/search') !== false && (isset($qs['list']) && strtolower($qs['list']) === 'favorite')) {
        $active_type = 'favorites';
    } elseif (strpos($path, '/search') !== false) {
        $active_type = 'search';
    } elseif (strpos($path, '/my') !== false) {
        $active_type = 'account';
    } elseif (strpos($path, '/category') !== false) {
        $active_type = 'catalog';
    }

    // Выбранные типы из настроек
    $types = array();
    for ($i = 1; $i <= 5; $i++) {
        $k = 'button_'.$i;
        if (!empty($settings[$k]) && isset($map[$settings[$k]])) {
            $types[] = $settings[$k];
        }
    }
    if (!$types) return array();

    // Счётчики
    $cart_count = 0;
    try { $cart_count = (int) (new shopCart())->count(); } catch (Exception $e) {}
    $fav_count  = $this->getFavoriteCount();
    $cmp_count  = $this->getCompareCount();

    $out = array();
    foreach ($types as $t) {
        $btn = array(
            'type'         => $t,
            'label'        => $map[$t],
            'url'          => '#',
            'action'       => null,
            'icon'         => $this->icon($t),
            'is_active'    => ($t === $active_type),
            'counter_html' => ''
        );

        switch ($t) {
            case 'home':
                $btn['url'] = wa()->getRouteUrl('shop/frontend');
                break;

            case 'catalog':
                $btn['action'] = 'catalog';
                break;

            case 'cart':
                $btn['url'] = wa()->getRouteUrl('shop/frontend/cart');
                $btn['counter_html'] =
                    '<span class="shop-mobiletoolbar__badge" data-mtb-cart-badge'
                    . ($cart_count ? '' : ' style="display:none"')
                    . '>' . (int)$cart_count . '</span>';
                break;

            case 'favorites':
                // /search/?list=favorite
                $btn['url'] = wa()->getRouteUrl('shop/frontend/search') . '?list=favorite';
                $btn['counter_html'] =
                    '<span class="shop-mobiletoolbar__badge" data-mtb-fav-badge'
                    . ($fav_count ? '' : ' style="display:none"')
                    . '>' . (int)$fav_count . '</span>';
                break;

            case 'compare':
                $btn['url'] = wa()->getRouteUrl('shop/frontend/compare');
                $btn['counter_html'] =
                    '<span class="shop-mobiletoolbar__badge" data-mtb-compare-badge'
                    . ($cmp_count ? '' : ' style="display:none"')
                    . '>' . (int)$cmp_count . '</span>';
                break;

            case 'search':
                $btn['action'] = 'search';
                break;

            case 'account':
                $btn['url'] = wa()->getRouteUrl('shop/frontend/my');
                break;
        }

        $out[] = $btn;
    }

    return $out;
}

/** Кол-во в сравнениях */
private function getCompareCount()
{
    try {
        // 1) server storage (гости/сеанс)
        $ids = wa()->getStorage()->get('shop/compare');
        if (is_array($ids)) {
            return count(array_unique(array_map('intval', $ids)));
        }
        // 2) cookie (если тема так делает)
        $cookie = waRequest::cookie('shop_compare', '', waRequest::TYPE_STRING_TRIM);
        if ($cookie) {
            $ids = array_unique(array_filter(array_map('intval', preg_split('/[,\s]+/', $cookie))));
            return count($ids);
        }
    } catch (Exception $e) {}
    return 0;
}

/** Кол-во в избранном */
private function getFavoriteCount()
{
    try {
        // 1) server storage (гости)
        $ids = wa()->getStorage()->get('shop/favorite');
        if (is_array($ids) && $ids) {
            return count(array_unique(array_map('intval', $ids)));
        }
        // 2) cookie
        $cookie = waRequest::cookie('shop_favorites', '', waRequest::TYPE_STRING_TRIM);
        if (!$cookie) $cookie = waRequest::cookie('shop_favorite', '', waRequest::TYPE_STRING_TRIM);
        if ($cookie) {
            $ids = array_unique(array_filter(array_map('intval', preg_split('/[,\s]+/', $cookie))));
            return count($ids);
        }
        // 3) БД (авторизованные)
        if (wa()->getUser()->isAuth()) {
            $m   = new waModel();
            $cid = (int) wa()->getUser()->getId();
            $sql = "SELECT COUNT(*) FROM shop_favorite WHERE contact_id = i:cid";
            return (int) $m->query($sql, ['cid' => $cid])->fetchField();
        }
    } catch (Exception $e) {}
    return 0;
}


/* Рекурсивно собираем ID из разных структур (массив значений, map[id=>1], вложенные массивы) */
private function flattenIdCount($node)
{
    if (!is_array($node)) return 0;
    $ids = array();
    $walk = function($x) use (&$walk, &$ids) {
        if (is_array($x)) {
            foreach ($x as $k => $v) {
                if (is_array($v)) {
                    $walk($v);
                } else {
                    // если ключ числовой — это id; иначе пробуем значение
                    if (is_numeric($k)) {
                        $ids[] = (int) $k;
                    } elseif (is_numeric($v)) {
                        $ids[] = (int) $v;
                    } else {
                        // иногда значения — bool; такие элементы просто учитываем как 1 шт.
                        $ids[] = null;
                    }
                }
            }
        }
    };
    $walk($node);

    // Удаляем null (если bool, но без id — считаем уникально по позиции)
    $clean = array();
    foreach ($ids as $i) {
        if ($i === null) {
            // счётчик «без id»; учитываем как 1 элемент
            $clean[] = uniqid('x', true);
        } else {
            $clean[] = (int) $i;
        }
    }
    return count(array_unique($clean));
}

    private function icon($type)
    {
        $icons = array(
            'home'      => '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M12 3l9 8h-3v10h-5v-6H11v6H6V11H3z"/></svg>',
            'catalog'   => '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M4 5h7v6H4zM13 5h7v6h-7zM4 13h7v6H4zM13 13h7v6h-7z"/></svg>',
            'cart'      => '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M7 18a2 2 0 100 4 2 2 0 000-4zm10 0a2 2 0 100 4 2 2 0 000-4zM6 6h15l-1.5 8.5a2 2 0 01-2 1.5H8a2 2 0 01-2-1.5L4 4H2"/></svg>',
            'favorites' => '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M12 21l-1.45-1.32C5.4 15.36 2 12.28 2 8.5 2 6 4 4 6.5 4A5.8 5.8 0 0112 6.09 5.8 5.8 0 0117.5 4C20 4 22 6 22 8.5c0 3.78-3.4 6.86-8.55 11.18L12 21z"/></svg>',
            'compare'   => '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M3 4h6v16H3zM15 10h6v10h-6z"/></svg>',
            'search'    => '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M21 21l-4.35-4.35M10 18a8 8 0 110-16 8 8 0 010 16z" stroke="currentColor" fill="none" stroke-width="2"/></svg>',
            'account'   => '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M12 12a5 5 0 100-10 5 5 0 000 10zm0 2c-5.33 0-8 3-8 6v2h16v-2c0-3-2.67-6-8-6z"/></svg>',
        );
        return isset($icons[$type]) ? $icons[$type] : '';
    }

    private function getCatalogTree()
    {
        $m = new shopCategoryModel();

        // Берём базовые поля
        $rows = $m->select('id,name,url,full_url,parent_id,status')
                  ->order('parent_id ASC, id ASC')
                  ->fetchAll('id');

        // Подготовить контейнер children
        foreach ($rows as &$r) {
            if (!isset($r['children'])) $r['children'] = array();
        }
        unset($r);

        // Строим дерево по parent_id
        $tree = array();
        foreach ($rows as $id => &$r) {
            $pid = (int) ifset($r['parent_id'], 0);
            if ($pid && isset($rows[$pid])) {
                $rows[$pid]['children'][] =& $r;
            } else {
                $tree[] =& $r; // корневые
            }
        }
        unset($r);

        // Строгое удаление скрытых узлов и ВСЕХ их потомков
        $tree = $this->pruneHiddenStrict($tree);

        // Прописываем frontend_url рекурсивно
        $this->fillUrls($tree);

        return $tree;
    }

    /**
     * Строгое удаление: если узел скрыт (status=0 или другие маркеры),
     * удаляем сам узел и весь его поддеревом.
     */
    private function pruneHiddenStrict(array $nodes)
    {
        $out = array();
        foreach ($nodes as $n) {
            $is_hidden =
                (isset($n['status']) && (int)$n['status'] === 0) ||
                !empty($n['hidden']) ||
                !empty($n['is_hidden']) ||
                (isset($n['visibility']) && $n['visibility'] === 'hidden');

            if ($is_hidden) {
                // Пропускаем весь поддерево
                continue;
            }

            if (!empty($n['children'])) {
                $n['children'] = $this->pruneHiddenStrict($n['children']);
            }
            $out[] = $n;
        }
        return $out;
    }

    /**
     * Лояльный фильтр (на всякий случай; не используется сейчас).
     */
    private function filterVisibleTree(array $nodes)
    {
        $out = array();
        foreach ($nodes as $n) {
            $hidden = (isset($n['status']) && (int)$n['status'] === 0)
                   || !empty($n['hidden'])
                   || !empty($n['is_hidden'])
                   || (isset($n['visibility']) && $n['visibility'] === 'hidden');
            if ($hidden) continue;
            if (!empty($n['children'])) $n['children'] = $this->filterVisibleTree($n['children']);
            $out[] = $n;
        }
        return $out;
    }

    private function fillUrls(array &$nodes)
    {
        foreach ($nodes as &$n) {
            $full = !empty($n['full_url']) ? $n['full_url'] : ltrim(ifset($n['url'], ''), '/');
            $n['frontend_url'] = $full
                ? wa()->getRouteUrl('shop/frontend/category', array('category_url' => $full))
                : wa()->getRouteUrl('shop/frontend');

            if (!empty($n['children'])) {
                $this->fillUrls($n['children']);
            }
        }
        unset($n);
    }
}
