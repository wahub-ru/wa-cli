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
 * This function is attached to the smarty factory object and sets separator between elements of array
 * @param array|object $array
 * @param string $sep
 * @return string
 */
function smarty_modifier_sep($array, $sep = ' ')
{
	if (!is_array($array))
	{
		$array = array($array);
	}

	return implode($sep, $array);
}

