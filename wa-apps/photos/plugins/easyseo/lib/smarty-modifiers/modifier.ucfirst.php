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
 * This function is attached to the smarty factory object and sets first letter to upper case
 * @param string $string
 * @return string
 */
function smarty_modifier_ucfirst($string)
{
	$fc = mb_strtoupper(mb_substr($string, 0, 1));

	return $fc . mb_substr($string, 1);
}

