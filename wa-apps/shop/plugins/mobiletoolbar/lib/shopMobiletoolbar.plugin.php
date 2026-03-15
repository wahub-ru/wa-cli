<?php
/**
 * Mobile toolbar plugin: category navigation with nested levels.
 * - Shows nested categories.
 * - "View all products" link above subcategories when inside a category.
 * - Excludes subtrees where parent category is hidden (status=0).
 */
class shopMobiletoolbarPlugin extends shopPlugin
{
    public function frontendLayout()
    {
        try {
            $view = wa()->getView();

            // Detect current category (for "View all products" and header)
            $current_category_id = null;
            $route_params = waRequest::param(); // storefront route params
            if (!empty($route_params['category_url'])) {
                $full = trim($route_params['category_url'], '/');
                $cm = new shopCategoryModel();
                $cat = $cm->getByField('full_url', $full);
                if ($cat && !empty($cat['id'])) {
                    $current_category_id = (int)$cat['id'];
                }
            }

            // Build category tree excluding hidden parents and their children
            $m = new waModel();
            // Note: we avoid left_key/right_key specifics and build an adjacency tree, relying on parent-child.
            $rows = $m->query("SELECT id, parent_id, name, url, full_url, status, sort FROM shop_category ORDER BY parent_id ASC, sort ASC, id ASC")->fetchAll();
            $by_parent = [];
            $by_id = [];
            foreach ($rows as $r) {
                $r['id'] = (int)$r['id'];
                $r['parent_id'] = (int)$r['parent_id'];
                $by_id[$r['id']] = $r;
                if (!isset($by_parent[$r['parent_id']])) {
                    $by_parent[$r['parent_id']] = [];
                }
                $by_parent[$r['parent_id']][] = $r['id'];
            }

            $route = wa()->getRouting();
            $build_url = function($full_url) use ($route) {
                // Build category URL for current route
                return wa()->getRouteUrl('shop/frontend/category', ['category_url' => trim($full_url, '/')]);
            };

            $make_node = function($r) use ($build_url) {
                return [
                    'id'   => (int)$r['id'],
                    'pid'  => (int)$r['parent_id'],
                    'name' => $r['name'],
                    'url'  => $build_url($r['full_url']),
                    'children' => []
                ];
            };

            // DFS that skips a category if its status=0 (and entire subtree)
            $build_tree = function($parent_id) use (&$build_tree, $by_parent, $by_id, $make_node) {
                $out = [];
                if (empty($by_parent[$parent_id])) {
                    return $out;
                }
                foreach ($by_parent[$parent_id] as $cid) {
                    $r = $by_id[$cid];
                    if ((int)$r['status'] !== 1) {
                        // Skip this category and all descendants
                        continue;
                    }
                    $node = $make_node($r);
                    $node['children'] = $build_tree($cid);
                    $out[] = $node;
                }
                return $out;
            };

            $tree = $build_tree(0);

            $template_path = wa()->getAppPath('plugins/'.$this->id.'/templates/frontend_panel.html', 'shop');

            $view->assign([
                'mobiletoolbar_categories_json' => json_encode($tree, JSON_UNESCAPED_UNICODE),
                'mobiletoolbar_current_category_id' => $current_category_id,
            ]);

            return $view->fetch($template_path);
        } catch (Exception $e) {
            waLog::log('mobiletoolbar frontendLayout error: '.$e->getMessage(), 'mobiletoolbar.log');
        }
        return null;
    }
}
