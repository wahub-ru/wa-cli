<?php
/*
 *
 * Easyseo plugin for Webasyst framework, created for Photos app.
 *
 * @name Easyseo
 * @author EasyIT LLC
 * @link https://easy-it.ru/
 * @copyright Copyright (c) 2017, EasyIT LLC
 * @version 1.0.0, 2025-10-01
 *
 */

/**
 * This function is attached to the smarty factory object and reshuffles the array every time
 * @param array $words
 * @return array
 */
function smarty_modifier_randomize($words)
{
	if (!is_array($words) || count($words) === 0)
	{
		return [];
	}

  $randomized_words = array_values($words);
  shuffle($randomized_words);

	return $randomized_words;
}