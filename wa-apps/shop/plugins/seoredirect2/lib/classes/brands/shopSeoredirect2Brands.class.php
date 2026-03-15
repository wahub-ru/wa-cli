<?php

class shopSeoredirect2Brands
{
	protected static $existsBrands;
	protected static $brands;
	protected static $feature;

	public static function existsBrands()
	{
		if (is_null(self::$existsBrands))
		{
			$info = wa('shop')->getConfig()->getPluginInfo('productbrands');

			self::$existsBrands = class_exists('shopProductbrandsPlugin') && $info !== array();
		}

		return self::$existsBrands;
	}

	public static function getFeatureId()
	{
		if (!self::existsBrands())
		{
			return -1;
		}

		return (int)wa()->getSetting('feature_id', '', array('shop', 'productbrands'));
	}

	protected static function getFeature()
	{
		if (self::$feature === null) {
			self::$feature = array();
			$feature_id = wa()->getSetting('feature_id', null, array('shop', 'productbrands'));
			if ($feature_id) {
				$feature_model = new shopFeatureModel();
				if ($feature = $feature_model->getById($feature_id)) {
					self::$feature = $feature;
				}
			}
		}
		return self::$feature;
	}

	public static function getBrands()
	{
		if (!self::existsBrands())
		{
			return array();
		}
		if (self::$brands === null) {
			$feature = self::getFeature();
			if ($feature) {
				$feature_model = new shopFeatureModel();
				$brands = $feature_model->getFeatureValues($feature);
				$product_features_model = new shopProductFeaturesModel();
				$types = array();
				if (wa()->getEnv() == 'frontend' && waRequest::param('type_id') && is_array(waRequest::param('type_id'))) {
					$types = waRequest::param('type_id');
				}
				$sql = "SELECT feature_value_id, COUNT(*) FROM " . $product_features_model->getTableName() . " pf
                        JOIN shop_product p ON pf.product_id = p.id
                        WHERE pf.feature_id = i:0 AND pf.sku_id IS NULL " . (wa()->getEnv() == 'frontend' ? "AND p.status = 1 " : '') .
					($types ? 'AND p.type_id IN (i:1) ' : '') .
					"GROUP BY pf.feature_value_id";
				$counts = $product_features_model->query($sql, $feature['id'], $types)->fetchAll('feature_value_id', true);
			} else {
				$brands = array();
				$counts = array();
			}

			if ($brands) {
				$brands_model = new shopProductbrandsModel();
				$rows = $brands_model->getById(array_keys($brands));

				foreach ($brands as $id => $name) {
					if (wa()->getEnv() == 'frontend' && !isset($counts[$id])) {
						unset($brands[$id]);
						continue;
					}
					if (isset($rows[$id])) {
						$brands[$id] = $rows[$id];
						$brands[$id]['name'] = $name;
						$brands[$id]['params'] = shopProductbrandsModel::getParams($brands[$id]['params']);
					} else {
						$brands[$id] = array(
							'id' => $id,
							//'name' => $name,
							//'summary' => '',
							//'description' => '',
							//'image' => null,
							'url' => null,
							//'filter' => '',
							'hidden' => 0,
							//'params' => array()
						);
					}
					if (wa()->getEnv() == 'frontend') {
						if ($brands[$id]['hidden']) {
							unset($brands[$id]);
							continue;
						}
						$brand_url = $brands[$id]['url'] ? $brands[$id]['url'] : urlencode($name);
						//$brands[$id]['url'] = str_replace('%BRAND%', $brand_url, $url);
						$brands[$id]['url'] = $brand_url;
					}
					$brands[$id]['count'] = isset($counts[$id]) ? $counts[$id] : 0;
				}
			}

			self::$brands = $brands;
		}
		return self::$brands;
	}

	public static function getBrandByUrl($url)
	{
		$brands = self::getBrands();

		foreach ($brands as $brand)
		{
			if ($brand['url'] == $url) {
				return $brand;
			}
		}

		return null;
	}

	public static function hasBrandCategory($brand_id, $category_id)
	{
		$feature_id = self::getFeatureId();
		$sql = 'SELECT cp.category_id, COUNT(DISTINCT cp.product_id) product_count FROM shop_category_products cp
JOIN shop_product_features pf ON cp.product_id = pf.product_id
WHERE pf.feature_id = ' . $feature_id . ' AND pf.feature_value_id = ' . $brand_id . '
GROUP BY cp.category_id';
		$model = new waModel();
		$categories = $model->query($sql)->fetchAll('category_id');
		if (isset($categories[$category_id]))
		{
			return (int)$categories[$category_id]['product_count'] > 0;
		}

		return false;
	}
}