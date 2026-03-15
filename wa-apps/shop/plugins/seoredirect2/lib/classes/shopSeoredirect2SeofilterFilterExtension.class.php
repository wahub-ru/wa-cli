<?php


class shopSeoredirect2SeofilterFilterExtension
{
	public static function isAppliedToStorefrontCategory(shopSeofilterFilter $filter, $storefront, $category_id)
	{
		if (method_exists($filter, 'isAppliedToStorefrontCategory'))
		{
			$result = $filter->isAppliedToStorefrontCategory($storefront, $category_id);
			$currency = shopSeofilterProductfiltersHelper::getCurrency();

			if (class_exists('shopSeofilterFilterTreeSettings'))
			{
				$tree_settings = new shopSeofilterFilterTreeSettings();
				$result = $result && $tree_settings->isFilterEnabled($storefront, $category_id, $filter);
			}

			if (class_exists('shopSeofilterFilterFeaturesValidator'))
			{
				$seofilter_settings = shopSeofilterBasicSettingsModel::getSettings();

				if ($seofilter_settings->consider_category_filters)
				{
					$validator = new shopSeofilterFilterFeaturesValidator();
					$params = $filter->getFeatureValuesAsFilterParamsForCurrency($currency);
					$result = $result && $validator->validateCategoryParams($category_id, $params);
				}
			}

			return $result && $filter->countProducts($category_id, $currency) > 0;
		}

		if ($filter->storefronts_use_mode == shopSeofilterFilter::USE_MODE_LISTED && !in_array($storefront, $filter->filter_storefronts))
		{
			return false;
		}

		if ($filter->storefronts_use_mode == shopSeofilterFilter::USE_MODE_EXCEPT && in_array($storefront, $filter->filter_storefronts))
		{
			return false;
		}

		if ($filter->categories_use_mode == shopSeofilterFilter::USE_MODE_LISTED && !in_array($category_id, $filter->filter_categories))
		{
			return false;
		}

		if ($filter->categories_use_mode == shopSeofilterFilter::USE_MODE_EXCEPT && in_array($category_id, $filter->filter_categories))
		{
			return false;
		}

		return true;
	}
}