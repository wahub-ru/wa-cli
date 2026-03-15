<?php

/**
 * Helper class shopApiextensionPluginProduct
 *
 * @author Steemy, created by 25.08.2021
 */

class shopApiextensionPluginProduct
{
    /**
     * Получить фото товаров
     * @param $productIds - список ид товаров
     * @return array
     * @throws waDbException
     * @throws waException
     */
    public function productImages($productIds)
    {
        if(!$productIds) return array();

        if(is_array($productIds)) {
            $productIds = implode(',', $productIds);
        }

        if ($cache = wa('shop')->getCache()) {
            $productImages = $cache->get('apiextension_product_images_' . $productIds);
            if ($productImages !== null) {
                return $productImages;
            }
        }

        $productImages = array();
        $productImagesModel = new shopProductImagesModel();

        $productImagesAll =
            $productImagesModel
                ->select('*')
                ->where('product_id IN(' . $productIds . ')')
                ->order('sort ASC')
                ->fetchAll();

        foreach($productImagesAll as $image) {
            $productImages[$image['product_id']][$image['id']] = $image;
        }

        if (!empty($cache) && $productIds) {
            $cache->set('apiextension_product_images_' . $productIds, $productImages, 7200);
        }

        return $productImages;
    }
}