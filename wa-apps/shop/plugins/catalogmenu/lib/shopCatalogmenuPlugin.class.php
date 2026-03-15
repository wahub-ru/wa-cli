<?php

class shopCatalogmenuPlugin extends shopPlugin
{
    public static function display()
    {
        $view = wa()->getView();
        $cache = wa()->getCache();
        $cache_key = 'catalog_menu_full_tree';

        // Проверяем, есть ли закешированное меню
        $cached_menu = $cache->get($cache_key);

        // Получаем категории первого уровня
        $category_model = new shopCategoryModel();
        $top_categories = $category_model->getTree(0);

        $view->assign('top_categories', $top_categories);
        $view->assign('has_cached_menu', !empty($cached_menu));

        return $view->fetch($this->path.'/templates/menu.html');
    }

    public function frontendHead()
    {
        $this->addJs('js/catalogmenu.js', true);
        $this->addCss('css/catalogmenu.css', true);

        return parent::frontendHead();
    }

    public static function getFullMenu()
    {
        $cache = wa()->getCache();
        $cache_key = 'catalog_menu_full_tree';

        // Пытаемся получить меню из кеша
        $menu = $cache->get($cache_key);

        if (!$menu) {
            // Если в кеше нет, строим полное дерево категорий
            $category_model = new shopCategoryModel();
            $full_tree = $category_model->getFullTree();

            // Форматируем данные для вывода
            $menu = self::formatMenu($full_tree);

            // Сохраняем в кеш на 24 часа
            $cache->set($cache_key, $menu, 86400);
        }

        return $menu;
    }

    private static function formatMenu($categories)
    {
        $result = array();
        foreach ($categories as $category) {
            $item = array(
                'id' => $category['id'],
                'name' => $category['name'],
                'url' => wa()->getRouteUrl('shop/frontend/category', array('category_url' => $category['full_url'])),
                'childs' => array()
            );

            if (!empty($category['childs'])) {
                $item['childs'] = self::formatMenu($category['childs']);
            }

            $result[] = $item;
        }

        return $result;
    }
}