<?php

/**
 * Helper class shopApiextensionPluginPromos
 *
 * @author Steemy, created by 05.06.2024
 */

class shopApiextensionPluginPromos
{
    /**
     * Товары из промо маркетинга
     * @param $promo_id
     * @return array|mixed
     * @throws waException
     */
    public function getProductFromPromos($promo_id)
    {
        if(!$promo_id) return array();

        $products = [];
        $products_data = [];

        $promo_rules_model = new shopPromoRulesModel();
        $rule = $promo_rules_model->getByField(['promo_id' => htmlspecialchars($promo_id), 'rule_type' => 'custom_price']);
        $product_ids = array_keys(ifempty($rule, 'rule_params', []));

        $hash = 'id/'.join(',', $product_ids);
        $collection = new shopProductsCollection($hash);
        $products_data = $collection->getProducts('*,skus_image,skus_filtered', 0, 10000, false);

        foreach ($product_ids as $id) {
            if (!empty($products_data[$id])) {
                $products[$id] = $products_data[$id];
            }
        }

        return $products;
    }
}