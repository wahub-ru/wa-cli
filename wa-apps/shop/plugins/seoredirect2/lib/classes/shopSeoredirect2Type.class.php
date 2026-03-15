<?php

abstract class shopSeoredirect2Type
{
	const CATEGORY = 0;
	const PRODUCT = 1;
	const PRODUCT_PAGE = 2;
	const PAGE = 3;
	const SEOFILTER = 4;
	const PRODUCT_REVIEWS = 5;
	const PRODUCTBRANDS = 6;
	const PRODUCTBRANDS_CATEGORY = 7;

	public static function getTypes()
	{
		return array(
			self::CATEGORY,
			self::PRODUCT,
			self::PRODUCT_PAGE,
			self::PAGE,
			self::SEOFILTER,
			self::PRODUCT_REVIEWS,
			self::PRODUCTBRANDS,
			self::PRODUCTBRANDS_CATEGORY,
		);
	}
}