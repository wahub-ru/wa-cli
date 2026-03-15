<?php

class shopMobiletoolbarPluginFrontendCountersController extends waJsonController
{
    public function execute()
    {
        $data = ['cart' => 0, 'favorite' => 0, 'compare' => 0];

        try { $data['cart'] = (int) (new shopCart())->count(); } catch (Exception $e) {}

        try {
            $ids = wa()->getStorage()->get('shop/compare');
            $data['compare'] = is_array($ids) ? count(array_unique(array_map('intval', $ids))) : 0;
        } catch (Exception $e) {}

        try {
            // favorites: storage → cookie → DB
            $count = 0;
            $ids = wa()->getStorage()->get('shop/favorite');
            if (is_array($ids) && $ids) {
                $count = count(array_unique(array_map('intval', $ids)));
            } else {
                $cookie = waRequest::cookie('shop_favorites', '', waRequest::TYPE_STRING_TRIM);
                if (!$cookie) $cookie = waRequest::cookie('shop_favorite', '', waRequest::TYPE_STRING_TRIM);
                if ($cookie) {
                    $ids = array_unique(array_filter(array_map('intval', preg_split('/[,\s]+/', $cookie))));
                    $count = count($ids);
                } elseif (wa()->getUser()->isAuth()) {
                    $m   = new waModel();
                    $cid = (int) wa()->getUser()->getId();
                    $sql = "SELECT COUNT(*) FROM shop_favorite WHERE contact_id = i:cid";
                    $count = (int) $m->query($sql, ['cid' => $cid])->fetchField();
                }
            }
            $data['favorite'] = $count;
        } catch (Exception $e) {}

        $this->response = $data;
    }
}
