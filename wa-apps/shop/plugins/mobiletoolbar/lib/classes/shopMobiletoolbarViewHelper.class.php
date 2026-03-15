<?php

class shopMobiletoolbarViewHelper extends waViewHelper
{
    public function panel($options = array())
    {
        wa('shop');
        /** @var shopMobiletoolbarPlugin $plugin */
        $plugin = wa('shop')->getPlugin('mobiletoolbar');
        if (!$plugin) { return ''; }

        $settings = $plugin->getSettings();
        if (empty($settings['enabled'])) { return ''; }

        // Кнопки
        $buttons = $this->prepareButtons($settings);
        if (!$buttons) { return ''; }

        // Данные для шаблона
        $view = wa()->getView();
        $view->assign(array(
            'mobiletoolbar_buttons'      => $buttons,
            'mobiletoolbar_catalog_tree' => $this->hasType($buttons, 'catalog') ? $this->getCatalogTree() : array(),
            'mobiletoolbar_has_search'   => $this->hasType($buttons, 'search'),
            'mobiletoolbar_search_url'   => wa()->getRouteUrl('shop/frontend/search'),
        ));

        // HTML
        $tpl = wa()->getAppPath('plugins/mobiletoolbar/templates/frontend_panel.html', 'shop');
        $html = is_readable($tpl) ? $view->fetch($tpl) : '';

        // CSS/JS инлайном, чтобы не зависеть от {$wa->footer()}
        $css_file = wa()->getAppPath('plugins/mobiletoolbar/css/mobiletoolbar.css', 'shop');
        $js_file  = wa()->getAppPath('plugins/mobiletoolbar/js/mobiletoolbar.js', 'shop');
        $css = is_readable($css_file) ? file_get_contents($css_file) : '';
        $js  = is_readable($js_file)  ? file_get_contents($js_file)  : '';

        return ($css ? "<style id=\"shop-mobiletoolbar-styles\">".$css."</style>\n" : '')
             . $html
             . ($js  ? "\n<script id=\"shop-mobiletoolbar-script\">".$js."</script>\n" : '');
    }

    private function hasType(array $buttons, $type)
    {
        foreach ($buttons as $b) if ($b['type'] === $type) return true;
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

$uri  = wa()->getConfig()->getRequestUrl(false, true);
$path = parse_url($uri, PHP_URL_PATH);
$active_type = 'home';
if (strpos($path, '/cart') !== false)         { $active_type = 'cart'; }
elseif (strpos($path, '/favorites') !== false){ $active_type = 'favorites'; }
elseif (strpos($path, '/compare') !== false)  { $active_type = 'compare'; }
elseif (strpos($path, '/search') !== false)   { $active_type = 'search'; }
elseif (strpos($path, '/my') !== false)       { $active_type = 'account'; }
elseif (strpos($path, '/category') !== false) { $active_type = 'catalog'; }


        $types = array();
        for ($i = 1; $i <= 5; $i++) {
            $k = 'button_'.$i;
            if (!empty($settings[$k]) && isset($map[$settings[$k]])) {
                $types[] = $settings[$k];
            }
        }
        if (!$types) return array();

        // счётчик корзины
        $count = 0;
        try { $count = (int)(new shopCart())->count(); } catch (Exception $e) {}

        $buttons = array();
        foreach ($types as $t) {
            $btn = array(
                'type'   => $t,
                'label'  => $map[$t],
                'url'    => '#',
                'action' => null,
                'icon'   => $this->icon($t),
                'counter_html' => ''
            );
            switch ($t) {
                case 'home':    $btn['url'] = wa()->getRouteUrl('shop/frontend'); break;
                case 'catalog': $btn['action'] = 'catalog'; break;
                case 'cart':
                    $btn['url'] = wa()->getRouteUrl('shop/frontend/cart');
                    if ($count > 0) $btn['counter_html'] = '<span class="shop-mobiletoolbar__counter">'.$count.'</span>';
                    break;
                case 'favorites': $btn['url'] = wa()->getRouteUrl('shop/frontend/favorites'); break;
                case 'compare':   $btn['url'] = wa()->getRouteUrl('shop/frontend/compare'); break;
                case 'search':    $btn['action'] = 'search'; break;
                case 'account':   $btn['url'] = wa()->getRouteUrl('shop/frontend/my'); break;
            }
            $buttons[] = $btn;
        }
        return $buttons;
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

// Берём только корневые категории (parent_id = 0)
$roots = $m->getTree(0, true);

$visible = array();
foreach ($roots as $root) {
    if (!empty($root['status'])) {
        $v = $this->pruneHidden2($root, false);
        if (!empty($v)) {
            $visible[] = $v;
        }
    }
}

$this->fillUrls($visible);
return $visible;
}
}

$this->fillUrls($visible);
return $visible;
}

    private function fillUrls(array &$nodes)
    {
        foreach ($nodes as &$n) {
            $full = !empty($n['full_url']) ? $n['full_url'] : ltrim(ifset($n['url'], ''), '/');
            $n['frontend_url'] = $full ? wa()->getRouteUrl('shop/frontend/category', array('category_url' => $full)) : wa()->getRouteUrl('shop/frontend');
            if (!empty($n['children'])) $this->fillUrls($n['children']);
        }
        unset($n);
    }


/**
 * Удаляет из узла все скрытые ветки (status=0). Дети скрытых не поднимаются.
 */
private function pruneHidden(array $node)
{
    if (empty($node['status'])) {
        return array();
    }
    $clean = $node;
    $clean['children'] = array();
    if (!empty($node['children']) && is_array($node['children'])) {
        foreach ($node['children'] as $child) {
            if (!empty($child['status'])) {
                $p = $this->pruneHidden($child);
                if (!empty($p)) { $clean['children'][] = $p; }
            }
        }
    }
    return $clean;
}


/**
 * Рекурсивно вырезает ветки, если текущий узел или КАКОЙ-ЛИБО ЕГО ПРЕДОК скрыт.
 * $hidden_parent = true передаётся вниз по дереву.
 */
private function pruneHidden2(array $node, $hidden_parent = false)
{
    $is_hidden = $hidden_parent || empty($node['status']);
    if ($is_hidden) {
        return array();
    }
    $node['children'] = isset($node['children']) && is_array($node['children']) ? $node['children'] : array();
    $filtered = array();
    foreach ($node['children'] as $ch) {
        $v = $this->pruneHidden2($ch, false); // тек. узел видимый, поэтому дальше только по статусу детей
        if (!empty($v)) {
            $filtered[] = $v;
        }
    }
    $node['children'] = $filtered;
    return $node;
}
}
