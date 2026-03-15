<?php

class blogDzenPluginValidator
{
    protected $plugin;
    protected $helper;

    public function __construct(blogDzenPlugin $plugin, blogDzenPluginFeedHelper $helper)
    {
        $this->plugin = $plugin;
        $this->helper = $helper;
    }

    public function validate(array $data)
    {
        $title = trim((string) ifset($data, 'title', ''));
        $raw_content = (string) ifset($data, 'text', '');
        $link = trim((string) ifset($data, 'link', ''));
        $enclosure = trim((string) ifset($data, 'enclosure_url', ''));
        $short_description = trim((string) ifset($data, 'short_description', ''));

        $post_link = $this->makeAbsoluteUrl($link);
        $sanitize_result = $this->helper->sanitizeContentWithReport($raw_content);
        $full_text_html = (string) ifset($sanitize_result, 'html', '');
        $sanitize_report = (array) ifset($sanitize_result, 'report', array());

        $errors = array();
        $warnings = array();
        $checks = array();

        if ($title === '') {
            $errors[] = $this->issue('title_required', 'Поле title обязательно', 'title', 'Заполните заголовок записи.');
            $checks[] = $this->check('error', 'Заголовок', 'Заголовок отсутствует.');
        } else {
            $checks[] = $this->check('success', 'Заголовок', 'Заголовок заполнен.');
        }

        if ($post_link === '' || stripos($post_link, 'https://') !== 0) {
            $errors[] = $this->issue('link_invalid', 'Поле link должно быть абсолютным HTTPS URL', 'link', 'Проверьте URL записи и домен сайта.');
            $checks[] = $this->check('error', 'Ссылка', 'Ссылка должна быть абсолютной и начинаться с https://');
        } else {
            $checks[] = $this->check('success', 'Ссылка', 'Ссылка записи корректна.');
        }

        if ($full_text_html === '') {
            $errors[] = $this->issue('content_empty', 'Пустой контент после очистки', 'content:encoded', 'Добавьте текстовый контент в запись.');
            $checks[] = $this->check('error', 'Контент', 'После очистки контент пустой.');
        } else {
            $checks[] = $this->check('success', 'Контент', 'Контент успешно очищен.');
        }

        if ($this->hasSanitizeReplacements($sanitize_report)) {
            $warnings[] = $this->issue(
                'html_normalized',
                $this->buildSanitizeSummary($sanitize_report),
                'content:encoded',
                'Перед публикацией проверьте, что автозамены тегов не исказили смысл текста.'
            );
            $checks[] = $this->check('warning', 'Автозамены HTML', $this->buildSanitizeSummary($sanitize_report));
        } else {
            $checks[] = $this->check('success', 'Автозамены HTML', 'Запрещённые/неподдерживаемые теги не обнаружены.');
        }

        if (preg_match('#<(meta|title|article|script|style|object|embed|audio|video)\b#i', $full_text_html)) {
            $errors[] = $this->issue('forbidden_html', 'В content:encoded остались запрещённые теги', 'content:encoded', 'Удалите служебную HTML-разметку и неподдерживаемые embed-теги.');
            $checks[] = $this->check('error', 'HTML-теги', 'В контенте есть запрещённые теги.');
        } else {
            $checks[] = $this->check('success', 'HTML-теги', 'Запрещённые теги не найдены.');
        }

        $allowed_domains = $this->helper->getAllowedDomains($this->plugin);
        $inline_images = $this->helper->extractImageUrls($full_text_html);

        if ($inline_images) {
            $inline_ok = true;
            foreach ($inline_images as $img_url) {
                $validation = $this->helper->validateImageUrl($this->makeAbsoluteUrl($img_url), $allowed_domains);
                if (!$validation['ok']) {
                    $inline_ok = false;
                    $errors[] = $this->issue(
                        'inline_image_invalid',
                        'Проблема с inline-изображением: '.implode('; ', (array) ifset($validation, 'errors', array())),
                        'img',
                        'Используйте HTTPS изображение на разрешённом домене с достаточным размером.'
                    );
                }
                foreach ((array) ifset($validation, 'warnings', array()) as $warning) {
                    $warnings[] = $this->issue('inline_image_warning', 'Inline-изображение: '.$warning, 'img', 'Рекомендуется использовать изображения шириной от 700px.');
                }
            }

            $checks[] = $this->check($inline_ok ? 'success' : 'error', 'Inline-изображения', $inline_ok ? 'Все inline-изображения прошли проверку.' : 'Часть inline-изображений не прошла проверку.');
        } else {
            $checks[] = $this->check('success', 'Inline-изображения', 'Inline-изображения в тексте не найдены (это не является ошибкой).');
        }

        if ($enclosure === '') {
            $warnings[] = $this->issue('enclosure_missing', 'URL обложки (enclosure) не заполнен', 'enclosure', 'Рекомендуется добавить отдельную обложку для Дзена.');
            $checks[] = $this->check('warning', 'Обложка (enclosure)', 'Обложка не указана.');
        } else {
            $validation = $this->helper->validateImageUrl($this->makeAbsoluteUrl($enclosure), $allowed_domains);
            if (!$validation['ok']) {
                $errors[] = $this->issue(
                    'enclosure_invalid',
                    'Проблема с enclosure: '.implode('; ', (array) ifset($validation, 'errors', array())),
                    'enclosure',
                    'Замените обложку на валидное изображение (HTTPS, домен, размер, пиксели).'
                );
                $checks[] = $this->check('error', 'Обложка (enclosure)', 'Обложка не прошла валидацию.');
            } else {
                $checks[] = $this->check('success', 'Обложка (enclosure)', 'Обложка прошла валидацию.');
            }
            foreach ((array) ifset($validation, 'warnings', array()) as $warning) {
                $warnings[] = $this->issue('enclosure_warning', 'Обложка: '.$warning, 'enclosure', 'Рекомендуется использовать изображение шириной от 700px.');
            }
        }

        $default_enclosure = trim((string) $this->plugin->getSettings('default_enclosure_url', ''));
        if ($default_enclosure !== '') {
            $default_validation = $this->helper->validateImageUrl($this->makeAbsoluteUrl($default_enclosure), $allowed_domains);
            foreach ((array) ifset($default_validation, 'warnings', array()) as $warning) {
                $warnings[] = $this->issue('default_enclosure_warning', 'Обложка по умолчанию: '.$warning, 'default_enclosure_url', 'Проверьте настройки плагина: изображение по умолчанию желательно не уже 700px.');
            }
            if (!$default_validation['ok']) {
                $warnings[] = $this->issue('default_enclosure_invalid', 'Обложка по умолчанию может быть невалидной: '.implode('; ', (array) ifset($default_validation, 'errors', array())), 'default_enclosure_url', 'Проверьте URL изображения по умолчанию в настройках плагина.');
            }
        }
        if ($short_description === '') {
            $warnings[] = $this->issue('description_empty', 'Краткое описание пустое', 'description', 'Рекомендуется заполнить поле «Краткое описание» в карточке Дзен.');
            $checks[] = $this->check('warning', 'Краткое описание', 'Поле краткого описания не заполнено.');
        } elseif ($short_description !== trim(strip_tags($short_description))) {
            $errors[] = $this->issue('description_html', 'Краткое описание должно быть без HTML', 'description', 'Удалите HTML-теги из краткого описания.');
            $checks[] = $this->check('error', 'Краткое описание', 'Поле краткого описания содержит HTML.');
        } else {
            $checks[] = $this->check('success', 'Краткое описание', 'Краткое описание заполнено.');
        }

        return array(
            'status' => $errors ? 'fail' : ($warnings ? 'warn' : 'ok'),
            'errors' => $errors,
            'warnings' => $warnings,
            'checks' => $checks,
            'preview' => array(
                'title' => $title,
                'link' => $post_link,
                'description' => $short_description,
                'enclosure' => $enclosure,
                'full_text_html' => $full_text_html,
                'full_text' => $this->helper->convertHtmlToText($full_text_html),
                'sanitize_report' => $sanitize_report,
            ),
        );
    }

    protected function hasSanitizeReplacements(array $report)
    {
        return (int) ifset($report, 'replaced_h1_with_h2', 0) > 0
            || (int) ifset($report, 'replaced_h5_h6_with_h4', 0) > 0
            || (int) ifset($report, 'replaced_formatting_tags', 0) > 0
            || (int) ifset($report, 'replaced_unsupported_with_p', 0) > 0
            || (int) ifset($report, 'removed_iframe', 0) > 0
            || (int) ifset($report, 'removed_forbidden_tags', 0) > 0
            || (int) ifset($report, 'removed_comments', 0) > 0
            || (int) ifset($report, 'removed_smarty_blocks', 0) > 0
            || (int) ifset($report, 'flattened_block_wrappers', 0) > 0
            || (int) ifset($report, 'removed_empty_tags', 0) > 0
            || (int) ifset($report, 'normalized_text_whitespace', 0) > 0;
    }

    protected function buildSanitizeSummary(array $report)
    {
        $parts = array();

        $h1 = (int) ifset($report, 'replaced_h1_with_h2', 0);
        if ($h1 > 0) {
            $parts[] = 'h1→h2: '.$h1;
        }

        $h56 = (int) ifset($report, 'replaced_h5_h6_with_h4', 0);
        if ($h56 > 0) {
            $parts[] = 'h5/h6→h4: '.$h56;
        }

        $format = (int) ifset($report, 'replaced_formatting_tags', 0);
        if ($format > 0) {
            $parts[] = 'strong/em/strike→b/i/s: '.$format;
        }

        $unsupported = (int) ifset($report, 'replaced_unsupported_with_p', 0);
        if ($unsupported > 0) {
            $parts[] = 'неподдерживаемые теги→p: '.$unsupported;
        }

        $removed_iframe = (int) ifset($report, 'removed_iframe', 0);
        if ($removed_iframe > 0) {
            $parts[] = 'удалённые iframe: '.$removed_iframe;
        }

        $removed_forbidden = (int) ifset($report, 'removed_forbidden_tags', 0);
        if ($removed_forbidden > 0) {
            $parts[] = 'удалённые canvas/map/noscript/video/audio: '.$removed_forbidden;
        }

        $removed_comments = (int) ifset($report, 'removed_comments', 0);
        if ($removed_comments > 0) {
            $parts[] = 'удалённые HTML-комментарии: '.$removed_comments;
        }

        $removed_smarty = (int) ifset($report, 'removed_smarty_blocks', 0);
        if ($removed_smarty > 0) {
            $parts[] = 'удалённые Smarty-вызовы: '.$removed_smarty;
        }

        $flattened = (int) ifset($report, 'flattened_block_wrappers', 0);
        if ($flattened > 0) {
            $parts[] = 'убраны лишние блочные обёртки: '.$flattened;
        }

        $removed_empty = (int) ifset($report, 'removed_empty_tags', 0);
        if ($removed_empty > 0) {
            $parts[] = 'удалённые пустые теги: '.$removed_empty;
        }

        $normalized_text_whitespace = (int) ifset($report, 'normalized_text_whitespace', 0);
        if ($normalized_text_whitespace > 0) {
            $parts[] = 'нормализованы лишние пробелы в тексте: '.$normalized_text_whitespace;
        }

        if (!$parts) {
            return 'Автозамены тегов не выполнялись.';
        }

        return 'Выполнены автозамены: '.implode('; ', $parts).'.';
    }

    protected function check($level, $title, $message)
    {
        return array('level' => $level, 'title' => $title, 'message' => $message);
    }

    protected function issue($code, $message, $field, $hint)
    {
        return array('code' => $code, 'message' => $message, 'field' => $field, 'hint' => $hint);
    }

    protected function makeAbsoluteUrl($url)
    {
        $url = trim((string) $url);
        if ($url === '') {
            return '';
        }
        if (preg_match('#^https?://#i', $url)) {
            return $url;
        }
        if (strpos($url, '//') === 0) {
            return 'https:'.$url;
        }

        $base_url = rtrim((string) wa()->getRootUrl(true), '/');
        if (strpos($url, '/') === 0) {
            return $base_url.$url;
        }

        return $base_url.'/'.ltrim($url, '/');
    }
}
