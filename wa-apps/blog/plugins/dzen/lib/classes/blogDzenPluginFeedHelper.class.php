<?php

class blogDzenPluginFeedHelper
{
    const MAX_IMAGE_BYTES = 26214400; // 25MB
    const MIN_LONG_SIDE = 800;
    const MIN_SHORT_SIDE = 400;
    const MIN_IMAGE_WIDTH_WARNING = 700;
    const MIN_VIDEO_WIDTH = 800;
    const MIN_VIDEO_HEIGHT = 400;
    const MAX_LONG_SIDE = 6000;

    public function sanitizeContent($content)
    {
        $result = $this->sanitizeContentWithReport($content);
        return (string) ifset($result, 'html', '');
    }

    public function sanitizeContentWithReport($content)
    {
        $content = trim((string) $content);
        if ($content === '') {
            return array(
                'html' => '',
                'report' => $this->getEmptySanitizeReport(),
            );
        }

        $report = $this->getEmptySanitizeReport();
        $report['removed_smarty_blocks'] = $this->countSmartyBlocks($content);
        $content = $this->removeSmartyBlocks($content);

        $report['removed_comments'] = $this->countHtmlComments($content);
        $content = preg_replace('/<!--.*?-->/s', '', (string) $content);

        $dom = new DOMDocument('1.0', 'UTF-8');
        libxml_use_internal_errors(true);
        $dom->loadHTML('<?xml encoding="UTF-8"><div>'.$content.'</div>', LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);
        libxml_clear_errors();

        $root = $dom->documentElement;
        if (!$root) {
            return array(
                'html' => '',
                'report' => $this->getEmptySanitizeReport(),
            );
        }

        $this->sanitizeNode($dom, $root, $report);
        $this->normalizeRootStructure($root, $report);
        $this->normalizeWhitespaceNodes($root, $report);

        $result = '';
        foreach ($root->childNodes as $child) {
            $result .= $dom->saveHTML($child);
        }

        return array(
            'html' => trim($result),
            'report' => $report,
        );
    }

    public function convertHtmlToText($html)
    {
        $html = trim((string) $html);
        if ($html === '') {
            return '';
        }

        $text = preg_replace('#<(br|/p|/div|/li|/h[1-6]|/blockquote|/figcaption)[^>]*>#i', "\n", $html);
        $text = html_entity_decode(strip_tags((string) $text), ENT_QUOTES, 'UTF-8');
        $text = str_replace("\xC2\xA0", ' ', (string) $text);
        $text = preg_replace('/\r\n?|\x{2028}|\x{2029}/u', "\n", (string) $text);
        $text = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/u', '', (string) $text);
        $text = preg_replace('/[ \t]+/u', ' ', (string) $text);
        $text = preg_replace('/\n{3,}/u', "\n\n", (string) $text);

        return trim((string) $text);
    }

    public function extractImageUrls($content)
    {
        $result = array();
        if (!preg_match_all('/<img[^>]+src=["\']([^"\']+)["\']/i', (string) $content, $matches)) {
            return $result;
        }

        foreach ((array) ifset($matches, 1, array()) as $src) {
            $src = trim((string) $src);
            if ($src !== '') {
                $result[] = $src;
            }
        }

        return array_values(array_unique($result));
    }

    public function getAllowedDomains(blogDzenPlugin $plugin)
    {
        $setting = (string) $plugin->getSettings('allowed_domains', '');
        $domains = preg_split('/\s*[\n,; ]\s*/', $setting, -1, PREG_SPLIT_NO_EMPTY);

        $default_domain = (string) wa()->getRouting()->getDomain();
        if ($default_domain !== '') {
            $domains[] = $default_domain;
        }

        $root_host = (string) parse_url((string) wa()->getRootUrl(true), PHP_URL_HOST);
        if ($root_host !== '') {
            $domains[] = $root_host;
        }

        $normalized = array();
        foreach ($domains as $domain) {
            $domain = strtolower(trim((string) $domain));
            if ($domain !== '') {
                $normalized[$domain] = true;
            }
        }

        return array_keys($normalized);
    }

    public function validateImageUrl($url, array $allowed_domains)
    {
        $errors = array();
        $warnings = array();
        $url = trim((string) $url);

        if ($url === '') {
            return array('ok' => false, 'errors' => array('Пустой URL изображения'), 'warnings' => array());
        }

        if (!preg_match('#^https://#i', $url)) {
            $errors[] = 'URL изображения должен использовать HTTPS';
        }

        $host = strtolower((string) parse_url($url, PHP_URL_HOST));
        if ($host === '') {
            $errors[] = 'Некорректный домен изображения';
        } elseif ($allowed_domains && !in_array($host, $allowed_domains, true)) {
            $errors[] = 'Домен изображения не входит в разрешённый список';
        }

        $headers = $this->fetchHeaders($url);
        $content_type = strtolower((string) ifset($headers, 'content-type', ''));
        $content_type = trim((string) preg_replace('/;.*$/', '', $content_type));
        if ($content_type !== '' && !preg_match('#^image/(jpeg|png|webp)$#i', $content_type)) {
            $errors[] = 'Неподдерживаемый тип изображения: '.$content_type;
        }

        $content_length = (int) ifset($headers, 'content-length', 0);
        if ($content_length > self::MAX_IMAGE_BYTES) {
            $errors[] = 'Размер изображения превышает 25MB';
        }

        $width = 0;
        $height = 0;
        $size = $this->fetchImageSize($url);
        if ($size) {
            $width = (int) ifset($size, 0, 0);
            $height = (int) ifset($size, 1, 0);
            $long = max($width, $height);
            $short = min($width, $height);

            if ($long < self::MIN_LONG_SIDE || $short < self::MIN_SHORT_SIDE) {
                $errors[] = 'Размер изображения меньше минимального (длинная сторона ≥ 800, короткая ≥ 400)';
            }
            if ($width > 0 && $width < self::MIN_IMAGE_WIDTH_WARNING) {
                $warnings[] = 'Ширина изображения меньше рекомендуемой (700px): '.$width.'px';
            }
            if ($long > self::MAX_LONG_SIDE) {
                $errors[] = 'Размер изображения превышает максимально допустимую длинную сторону (6000px)';
            }
        } else {
            $errors[] = 'Не удалось получить размеры изображения';
        }

        return array(
            'ok' => !$errors,
            'errors' => $errors,
            'warnings' => $warnings,
            'content_type' => $content_type ?: 'image/jpeg',
            'content_length' => $content_length,
            'width' => $width,
            'height' => $height,
        );
    }

    public function validateVideoUrl($url, array $allowed_domains)
    {
        $errors = array();
        $warnings = array();
        $url = trim((string) $url);

        if ($url === '') {
            return array('ok' => false, 'errors' => array('Пустой URL видео'), 'warnings' => array(), 'width' => 0, 'height' => 0);
        }

        if (!preg_match('#^https://#i', $url)) {
            $errors[] = 'URL видео должен использовать HTTPS';
        }

        $host = strtolower((string) parse_url($url, PHP_URL_HOST));
        if ($host === '') {
            $errors[] = 'Некорректный домен видео';
        } elseif ($allowed_domains && !in_array($host, $allowed_domains, true)) {
            $errors[] = 'Домен видео не входит в разрешённый список';
        }

        $headers = $this->fetchHeaders($url);
        $content_type = strtolower((string) ifset($headers, 'content-type', ''));
        $content_type = trim((string) preg_replace('/;.*$/', '', $content_type));
        if ($content_type !== '' && stripos($content_type, 'video/') !== 0) {
            $warnings[] = 'Content-Type не похож на video/*: '.$content_type;
        }

        $size = $this->fetchImageSize($url);
        $width = (int) ifset($size, 0, 0);
        $height = (int) ifset($size, 1, 0);

        if ($width > 0 && $height > 0) {
            if ($width < self::MIN_VIDEO_WIDTH || $height < self::MIN_VIDEO_HEIGHT) {
                $warnings[] = 'Разрешение видео ниже рекомендуемого минимума 800x400: '.$width.'x'.$height;
            }
        } else {
            $warnings[] = 'Не удалось автоматически определить разрешение видео';
        }

        return array(
            'ok' => !$errors,
            'errors' => $errors,
            'warnings' => $warnings,
            'width' => $width,
            'height' => $height,
            'content_type' => $content_type,
        );
    }

    protected function sanitizeNode(DOMDocument $dom, DOMNode $node, array &$report)
    {
        for ($child = $node->firstChild; $child !== null;) {
            $next = $child->nextSibling;
            if ($child->nodeType === XML_COMMENT_NODE) {
                $report['removed_comments']++;
                $this->removeNode($child);
                $child = $next;
                continue;
            }

            if ($child->nodeType === XML_ELEMENT_NODE) {
                $tag = strtolower($child->nodeName);

                if ($tag === 'h1') {
                    $child = $this->renameTag($dom, $child, 'h2');
                    $tag = 'h2';
                    $report['replaced_h1_with_h2']++;
                } elseif ($tag === 'h5' || $tag === 'h6') {
                    $child = $this->renameTag($dom, $child, 'h4');
                    $tag = 'h4';
                    $report['replaced_h5_h6_with_h4']++;
                } elseif ($tag === 'strong') {
                    $child = $this->renameTag($dom, $child, 'b');
                    $tag = 'b';
                    $report['replaced_formatting_tags']++;
                } elseif ($tag === 'em') {
                    $child = $this->renameTag($dom, $child, 'i');
                    $tag = 'i';
                    $report['replaced_formatting_tags']++;
                } elseif ($tag === 'strike') {
                    $child = $this->renameTag($dom, $child, 's');
                    $tag = 's';
                    $report['replaced_formatting_tags']++;
                }

                if (in_array($tag, array('canvas', 'map', 'noscript', 'video', 'audio'), true)) {
                    $report['removed_forbidden_tags']++;
                    $this->removeNode($child);
                    $child = $next;
                    continue;
                }

                if ($tag === 'iframe') {
                    $src = trim((string) $child->getAttribute('src'));
                    if (!$this->isAllowedIframe($src)) {
                        $report['removed_iframe']++;
                        $this->removeNode($child);
                        $child = $next;
                        continue;
                    }

                    $this->sanitizeNode($dom, $child, $report);
                    $this->sanitizeAttributes($child, $tag);
                    $child = $next;
                    continue;
                }

                if (!$this->isAllowedTag($tag)) {
                    $child = $this->renameTag($dom, $child, 'p');
                    $tag = 'p';
                    $report['replaced_unsupported_with_p']++;
                }

                $this->sanitizeNode($dom, $child, $report);
                $this->sanitizeAttributes($child, $tag);

                if ($tag === 'li') {
                    $this->sanitizeListItem($child);
                }
            }
            $child = $next;
        }
    }


    protected function normalizeRootStructure(DOMNode $root, array &$report)
    {
        $changed = true;
        while ($changed) {
            $changed = false;
            for ($node = $root->firstChild; $node !== null;) {
                $next = $node->nextSibling;

                if ($node->nodeType === XML_ELEMENT_NODE) {
                    $tag = strtolower($node->nodeName);
                    if ($tag === 'p') {
                        if ($this->paragraphHasBlockChildren($node)) {
                            $this->unwrapNode($node);
                            $report['flattened_block_wrappers']++;
                            $changed = true;
                            $node = $next;
                            continue;
                        }

                        if ($this->isEmptyTagNode($node)) {
                            $this->removeNode($node);
                            $report['removed_empty_tags']++;
                            $changed = true;
                            $node = $next;
                            continue;
                        }
                    } elseif ($this->isEmptyTagNode($node)) {
                        $this->removeNode($node);
                        $report['removed_empty_tags']++;
                        $changed = true;
                        $node = $next;
                        continue;
                    }
                }

                $node = $next;
            }
        }
    }

    protected function paragraphHasBlockChildren(DOMNode $node)
    {
        for ($child = $node->firstChild; $child !== null; $child = $child->nextSibling) {
            if ($child->nodeType !== XML_ELEMENT_NODE) {
                continue;
            }

            $name = strtolower($child->nodeName);
            if (in_array($name, array('p', 'ul', 'ol', 'blockquote', 'figure', 'h2', 'h3', 'h4'), true)) {
                return true;
            }
        }

        return false;
    }

    protected function isEmptyTagNode(DOMNode $node)
    {
        if ($node->nodeType !== XML_ELEMENT_NODE) {
            return false;
        }

        $tag = strtolower($node->nodeName);
        if (in_array($tag, array('img', 'iframe', 'br'), true)) {
            return false;
        }

        $text = trim(preg_replace('/\x{00A0}/u', ' ', (string) $node->textContent));
        if ($text !== '') {
            return false;
        }

        for ($child = $node->firstChild; $child !== null; $child = $child->nextSibling) {
            if ($child->nodeType === XML_ELEMENT_NODE) {
                $child_tag = strtolower($child->nodeName);
                if (in_array($child_tag, array('img', 'iframe'), true)) {
                    return false;
                }
            }
        }

        return true;
    }


    protected function normalizeWhitespaceNodes(DOMNode $node, array &$report)
    {
        for ($child = $node->firstChild; $child !== null;) {
            $next = $child->nextSibling;

            if ($child->nodeType === XML_TEXT_NODE) {
                $value = (string) $child->nodeValue;
                $normalized = preg_replace('/\s+/u', ' ', $value);
                $normalized = trim((string) $normalized);

                if ($normalized === '') {
                    if (trim($value) !== '') {
                        $report['normalized_text_whitespace']++;
                    }
                    $this->removeNode($child);
                    $child = $next;
                    continue;
                }

                if ($normalized !== $value) {
                    $child->nodeValue = $normalized;
                    $report['normalized_text_whitespace']++;
                }
            } elseif ($child->nodeType === XML_ELEMENT_NODE) {
                $this->normalizeWhitespaceNodes($child, $report);
            }

            $child = $next;
        }
    }

    protected function isAllowedTag($tag)
    {
        return in_array($tag, array('p', 'br', 'h2', 'h3', 'h4', 'ul', 'ol', 'li', 'b', 'i', 'u', 's', 'a', 'img', 'blockquote', 'figure', 'figcaption', 'iframe'), true);
    }

    protected function sanitizeAttributes(DOMNode $node, $tag)
    {
        if (!$node->hasAttributes()) {
            return;
        }

        $allowed = array();
        if ($tag === 'a') {
            $allowed = array('href', 'title', 'rel', 'target');
        } elseif ($tag === 'img') {
            $allowed = array('src', 'alt', 'title');
        } elseif (in_array($tag, array('h2', 'h3', 'h4'), true)) {
            $allowed = array('id');
        } elseif ($tag === 'iframe') {
            $allowed = array('width', 'height', 'src', 'title', 'frameborder', 'allow', 'allowfullscreen');
        }

        for ($i = $node->attributes->length - 1; $i >= 0; $i--) {
            $attr = $node->attributes->item($i);
            $name = strtolower((string) $attr->name);
            if (strpos($name, 'on') === 0 || $name === 'style' || !in_array($name, $allowed, true)) {
                $node->removeAttribute($attr->name);
                continue;
            }

            $value = trim((string) $attr->value);
            if (($name === 'href' || $name === 'src') && !preg_match('#^(https://|/)#i', $value)) {
                $node->removeAttribute($attr->name);
            }
        }
    }

    protected function isAllowedIframe($src)
    {
        $src = trim((string) $src);
        if ($src === '') {
            return false;
        }

        return (bool) preg_match('#^https://(www\.)?youtube\.com/embed/[a-zA-Z0-9_-]+#i', $src);
    }

    protected function sanitizeListItem(DOMNode $node)
    {
        for ($child = $node->firstChild; $child !== null;) {
            $next = $child->nextSibling;
            if ($child->nodeType === XML_ELEMENT_NODE) {
                $name = strtolower($child->nodeName);
                if ($name !== 'br') {
                    $this->unwrapNode($child);
                }
            }
            $child = $next;
        }
    }

    protected function renameTag(DOMDocument $dom, DOMNode $node, $new_tag)
    {
        $replacement = $dom->createElement($new_tag);
        if ($node->hasAttributes()) {
            foreach ($node->attributes as $attribute) {
                $replacement->setAttribute($attribute->name, $attribute->value);
            }
        }

        while ($node->firstChild) {
            $replacement->appendChild($node->firstChild);
        }

        $node->parentNode->replaceChild($replacement, $node);
        return $replacement;
    }

    protected function unwrapNode(DOMNode $node)
    {
        $parent = $node->parentNode;
        if (!$parent) {
            return;
        }

        while ($node->firstChild) {
            $parent->insertBefore($node->firstChild, $node);
        }

        $parent->removeChild($node);
    }

    protected function removeNode(DOMNode $node)
    {
        $parent = $node->parentNode;
        if ($parent) {
            $parent->removeChild($node);
        }
    }

    protected function getEmptySanitizeReport()
    {
        return array(
            'replaced_h1_with_h2' => 0,
            'replaced_h5_h6_with_h4' => 0,
            'replaced_formatting_tags' => 0,
            'replaced_unsupported_with_p' => 0,
            'removed_iframe' => 0,
            'removed_forbidden_tags' => 0,
            'removed_comments' => 0,
            'removed_smarty_blocks' => 0,
            'flattened_block_wrappers' => 0,
            'removed_empty_tags' => 0,
            'normalized_text_whitespace' => 0,
        );
    }


    protected function countSmartyBlocks($content)
    {
        if (!preg_match_all('/\{\$[^}]+\}/u', (string) $content, $matches)) {
            return 0;
        }

        return count((array) ifset($matches, 0, array()));
    }

    protected function removeSmartyBlocks($content)
    {
        return preg_replace('/\{\$[^}]+\}/u', '', (string) $content);
    }

    protected function countHtmlComments($content)
    {
        if (!preg_match_all('/<!--.*?-->/s', (string) $content, $matches)) {
            return 0;
        }

        return count((array) ifset($matches, 0, array()));
    }

    protected function fetchHeaders($url)
    {
        $context = stream_context_create(array('http' => array('method' => 'HEAD', 'timeout' => 4, 'ignore_errors' => true)));
        $headers = @get_headers($url, 1, $context);
        if (!is_array($headers)) {
            return array();
        }

        $normalized = array();
        foreach ($headers as $k => $v) {
            if (is_string($k)) {
                $normalized[strtolower($k)] = is_array($v) ? (string) end($v) : (string) $v;
            }
        }

        return $normalized;
    }

    protected function fetchImageSize($url)
    {
        return @getimagesize($url);
    }
}
