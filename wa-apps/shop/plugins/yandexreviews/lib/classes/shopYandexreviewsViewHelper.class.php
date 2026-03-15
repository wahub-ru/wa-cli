<?php

/**
 * Вызов из шаблонов Shop:
 *   {shopYandexreviews::reviews view="tiles" limit=8 hide=1}
 * или (если поддерживается алиас-функция):
 *   {yandexreviews view="tiles" limit=8 hide=1}
 */
class shopYandexreviewsViewHelper extends waViewHelper
{
    /**
     * @param array|string $params  можно массивом или "view=tiles limit=8 hide=1"
     * @return string HTML
     */
    public function reviews($params = [])
    {
        // Приведём строковые параметры к массиву (на случай {yandexreviews view=...})
        if (is_string($params) && $params !== '') {
            $params = $this->parseArgsString($params);
        } elseif (!is_array($params)) {
            $params = [];
        }

        /** @var shopYandexreviewsPlugin|null $plugin */
        $plugin = wa('shop')->getPlugin('yandexreviews');
        if (!$plugin) {
            return '';
        }

        $settings = $plugin->getSettings();

        $view  = isset($params['view']) && in_array($params['view'], ['tiles','list'], true)
            ? $params['view']
            : ($settings['view_mode'] ?? 'tiles');

        $limit = isset($params['limit']) ? max(1, (int)$params['limit']) : 8;

        // hide: если не указали в вызове — берём из настроек
        if (array_key_exists('hide', $params)) {
            $hide = $this->toBool($params['hide']);
        } else {
            $hide = !empty($settings['hide_low_ratings']);
        }

        // Рендерим через наш рендерер
        try {
            $renderer = new YandexReviewsRenderer($plugin);
            return (string)$renderer->render($view, $limit, $hide);
        } catch (Exception $e) {
            waLog::log('yandexreviews helper error: '.$e->getMessage(), 'yandexreviews.log');
            return '';
        }
    }

    /* ===== utils ===== */

    private function parseArgsString(string $s): array
    {
        $out = [];
        if (preg_match_all('~(\w+)\s*=\s*(?:"([^"]*)"|\'([^\']*)\'|(\S+))~u', $s, $mm, PREG_SET_ORDER)) {
            foreach ($mm as $m) {
                $key = strtolower($m[1]);
                $val = $m[2] !== '' ? $m[2] : ($m[3] !== '' ? $m[3] : $m[4]);
                $out[$key] = $val;
            }
        }
        return $out;
    }

    private function toBool($v): bool
    {
        if (is_bool($v)) return $v;
        $v = strtolower((string)$v);
        return in_array($v, ['1','true','yes','on','y','да'], true);
    }
}
