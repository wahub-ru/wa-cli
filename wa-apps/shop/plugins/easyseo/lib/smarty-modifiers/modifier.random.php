<?php
/*
 *
 * Easyseo plugin for Webasyst framework, created for Shopscript app.
 *
 * @name Easyseo
 * @author EasyIT LLC
 * @link https://easy-it.ru/
 * @copyright Copyright (c) 2017, EasyIT LLC
 * @version 1.0.0, 2024-10-01
 *
 */

/**
 * This function is attached to the smarty factory object and selects specific element from array based on current url
 * @param array[mixed] $words
 * @return mixed
 */
function smarty_modifier_random($words)
{
	if (!is_array($words) || count($words) === 0)
	{
		return '';
	}

	$key = waRequest::server('HTTP_HOST') . '/'
		. wa()->getRouting()->getRootUrl()
		. wa()->getRouting()->getCurrentUrl()
		. implode(',', $words);

	$hex_digits_count = ceil(count($words) / 16 - 1e-6);

	$hash = md5($key);
	$random = hexdec(substr($hash, -$hex_digits_count, $hex_digits_count));

	return $words[$random % count($words)];
}