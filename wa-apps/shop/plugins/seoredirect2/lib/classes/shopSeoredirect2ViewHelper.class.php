<?php

class shopSeoredirect2ViewHelper
{
	/**
	 * Создаёт запись об ошибке для дальнейшего ручного устранения
	 *
	 * @param int|null $error_code
	 */
	public static function createErrorNode($error_code = null)
	{
		$plugin = shopSeoredirect2Plugin::getInstance();
		$settings = $plugin->getSettings();

		if ($error_code != 404 || !$settings['enable'] || !$settings['errors'])
		{
			return;
		}

		$domain = wa()->getRouting()->getDomain();
		$url = waRequest::server('REQUEST_URI');
		$redirect_model = new shopSeoredirect2RedirectModel();
		$has_redirect = $redirect_model->hasRedirect($domain, $url);

		if (!$has_redirect)
		{
			$errors_storage = new shopSeoredirect2ErrorStorage();
			$errors_storage->addError($domain, $url, $error_code);
		}
	}

	/**
	 * Проверяет валидность URL
	 *
	 * @param $url
	 * @return bool
	 */
	public static function isURL($url)
	{
		return !!filter_var($url, FILTER_VALIDATE_URL);
	}

	public static function getNameByType($type) {
		$type_name = array('Категория', 'Товар', 'Подстраница  товара', 'Инф. страница', 'SEO-фильтр', 'Отзывы', 'Брэнды');

		return isset($type_name[$type]) ? $type_name[$type]: "Нет такого";
	}

	public static function a($href, $text)
	{
		return '<a href="' . $href . '">' . $text . '</a>';
	}

	public static function getBackendUrlByData($autoredirect)
	{
		if ($autoredirect['id'] == 0)
		{
			return 'нет';
		}
		$id = $autoredirect['id'];
		$parent_id = $autoredirect['parent_id'];
		$bakend_url = wa_backend_url();
		switch ($autoredirect['type']) {
			case shopSeoredirect2Type::CATEGORY:
				return self::a($bakend_url. 'shop/?action=products#/products/category_id=' . $id, $id);
			case shopSeoredirect2Type::PRODUCT:
				return self::a($bakend_url. "shop/?action=products#/product/$id/", $id);
			case shopSeoredirect2Type::PRODUCT_PAGE:
				return self::a($bakend_url. "shop/?action=products#/product/{$parent_id}/edit/pages/{$id}/", $id);
			case shopSeoredirect2Type::PAGE:
				return self::a($bakend_url. 'shop/?action=storefronts#/pages/'.$id, $id);
			case shopSeoredirect2Type::SEOFILTER:
				return self::a($bakend_url. 'shop/?plugin=seofilter&action=edit&id=' . $id, $id);
			case shopSeoredirect2Type::PRODUCT_REVIEWS:
				return '';
			case shopSeoredirect2Type::PRODUCTBRANDS:
				return self::a($bakend_url. "shop/?action=products#/brand/{$id}/", $id);
			case shopSeoredirect2Type::PRODUCTBRANDS_CATEGORY:
				return self::a($bakend_url. "shop/?action=products#/products/category_id=".$id, $id);
		}

		return 'Нет';
	}

	public static function getParentBackendUrlByData($autoredirect)
	{
		$data = $autoredirect;
		$data['id'] = $autoredirect['parent_id'];
		switch ($autoredirect['type']) {
			case shopSeoredirect2Type::CATEGORY:
				break;
			case shopSeoredirect2Type::PRODUCT:
				$data['type'] = shopSeoredirect2Type::CATEGORY;
				break;
			case shopSeoredirect2Type::PRODUCT_PAGE:
				$data['type'] = shopSeoredirect2Type::PRODUCT;
				break;
			case shopSeoredirect2Type::PAGE:
				break;
			case shopSeoredirect2Type::SEOFILTER:
				$data['type'] = shopSeoredirect2Type::CATEGORY;
				break;
			case shopSeoredirect2Type::PRODUCT_REVIEWS:
				$data['type'] = shopSeoredirect2Type::PRODUCT;
				break;
			case shopSeoredirect2Type::PRODUCTBRANDS_CATEGORY:
				$data['type'] = shopSeoredirect2Type::PRODUCTBRANDS;
				break;
		}
		return self::getBackendUrlByData($data);
	}

	public static function truncate($string, $length = 80, $etc = '...', $break_words = false, $middle = false) {
		if ($length == 0)
			return '';

		if (Smarty::$_MBSTRING) {
			if (mb_strlen($string, Smarty::$_CHARSET) > $length) {
				$length -= min($length, mb_strlen($etc, Smarty::$_CHARSET));
				if (!$break_words && !$middle) {
					$string = preg_replace('/\s+?(\S+)?$/' . Smarty::$_UTF8_MODIFIER, '', mb_substr($string, 0, $length + 1, Smarty::$_CHARSET));
				}
				if (!$middle) {
					return mb_substr($string, 0, $length, Smarty::$_CHARSET) . $etc;
				}
				return mb_substr($string, 0, $length / 2, Smarty::$_CHARSET) . $etc . mb_substr($string, - $length / 2, $length, Smarty::$_CHARSET);
			}
			return $string;
		}

		// no MBString fallback
		if (isset($string[$length])) {
			$length -= min($length, strlen($etc));
			if (!$break_words && !$middle) {
				$string = preg_replace('/\s+?(\S+)?$/', '', substr($string, 0, $length + 1));
			}
			if (!$middle) {
				return substr($string, 0, $length) . $etc;
			}
			return substr($string, 0, $length / 2) . $etc . substr($string, - $length / 2);
		}
		return $string;
	}

	public static function date($format, $time = null, $timezone = null, $locale = null)
	{
		waLocale::loadByDomain('webasyst', $locale);

		$old_locale = waLocale::getLocale();
		if ($locale != $old_locale) {
			$locale = waSystem::getInstance()->getUser()->getLocale();
		}

		$cut_year = null;
		if ($format === 'shortdate') {
			$cut_year = date('Y');
			$format = 'humandate';
		}

		if ($format === 'humandatetime') {
			if (preg_match("/^[0-9]+$/", $time)) {
				$time = date("Y-m-d H:i:s", $time);
			}
			$date_time = new DateTime($time);
			$base_date_time = new DateTime(date("Y-m-d H:i:s",strtotime('-1 day')));
			if ($timezone) {
				$date_timezone = new DateTimeZone($timezone);
				$date_time->setTimezone($date_timezone);
				$base_date_time->setTimezone($date_timezone);
			}

			$day = $date_time->format('Y z');
			if ($base_date_time->format('Y z') === $day) {
				$result = _ws('Yesterday');
			} else {
				$base_date_time->modify('+1 day');
				if ($base_date_time->format('Y z') === $day) {
					$result = _ws('Today');
				} else {
					$base_date_time->modify('+1 day');
					if ($base_date_time->format('Y z') === $day) {
						$result = _ws('Tomorrow');
					} else {
						$result = waDateTime::date('d.m.Y', $time, $timezone, $locale); // waDateTime::getFormat('humandate', $locale)
					}
				}
			}

			$result = $result . ' ' .waDateTime::date(waDateTime::getFormat('time', $locale), $time, $timezone, $locale);
		} else {
			$result = waDateTime::date(waDateTime::getFormat($format, $locale), $time, $timezone, $locale);
		}

		if ($cut_year) {
			$result = str_replace($cut_year, '', $result);
			$result = trim($result, ' ,./\\');
		}

		if ($locale != $old_locale) {
			wa()->setLocale($old_locale);
		}
		return $result;
	}
}