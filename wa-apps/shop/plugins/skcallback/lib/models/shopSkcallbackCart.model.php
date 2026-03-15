<?php

class shopSkcallbackCartModel extends waModel{

    protected $table = 'shop_skcallback_cart';

    public function getCartByRequestId($request_id){

        $request_id = (int)$request_id;

        $sql = "SELECT t1.product_id, t1.sku_id, t1.quantity, t2.name, t2.price, t2.currency, t2.url, t2.image_id, t2.ext, t3.name as sku_name
                  FROM shop_skcallback_cart t1
                  JOIN shop_product t2 ON t1.product_id = t2.id
                  JOIN shop_product_skus t3 ON t1.sku_id = t3.id
                  WHERE t1.request_id = {$request_id}
                  ORDER BY t1.id ASC";

        $products = $this->query($sql)->fetchAll();

        foreach($products as &$product){
            $product['frontend_url'] = wa()->getRouteUrl('shop/frontend/product', array(
                'product_url' => $product['url'],
                'category_url' => ifset($product['category_url'], '')
            ));
        }

        return $products;

    }

}
