<?php

class blogDzenPluginFrontendFeedAction extends blogViewAction
{
    protected $base_url;
    protected $helper;

    public function execute()
    {
        $blog_id = $this->resolveRequestedBlogId();

        $available_blogs = blogHelper::getAvailable();
        if (!$available_blogs) {
            throw new waException('Blog is not defined', 404);
        }

        $plugin = waSystem::getInstance()->getPlugin('dzen');
        $this->helper = new blogDzenPluginFeedHelper();
        $feed_mode = (string) $plugin->getSettings('feed_mode', 'all_in_one');
        $selected_blog_ids = preg_split('/\s*,\s*/', (string) $plugin->getSettings('feed_blog_ids', ''), -1, PREG_SPLIT_NO_EMPTY);
        $selected_blog_ids = array_values(array_filter(array_map('intval', (array) $selected_blog_ids)));

        $allowed_blog_ids = array_keys($available_blogs);
        if ($selected_blog_ids) {
            $allowed_blog_ids = array_values(array_intersect($allowed_blog_ids, $selected_blog_ids));
        }
        if (!$allowed_blog_ids) {
            throw new waException('Blog is not defined', 404);
        }

        $search_options = array();
        if ($feed_mode === 'separate_by_blog') {
            if (!$blog_id) {
                throw new waException('Blog is not defined', 404);
            }
            if (!in_array($blog_id, $allowed_blog_ids, true)) {
                throw new waException('Blog is not allowed for feed', 404);
            }
            $search_options['blog_id'] = $blog_id;
        } else {
            $search_options['blog_id'] = $allowed_blog_ids;
            $blog_id = 0;
        }

        $limit  = (int) $plugin->getSettings('feed_posts_limit', 30);
        if ($limit < 0) {
            $limit = 0;
        }
        if ($limit === 0) {
            $limit = 100;
        }

        $this->base_url = rtrim((string) wa()->getRootUrl(true), '/');

        $options = array(
            'params' => false,
            'user'   => 'name',
        );
        $data = array('blog' => $available_blogs);

        $post_model  = new blogPostModel();
        $post_search = $post_model->search($search_options, $options, $data);

        if ($limit > 0) {
            $posts = $post_search->fetchSearchPage(1, $limit);
        } else {
            $post_search->fetchSearchPage(1, 1);
            $count = (int) $post_search->searchCount();
            $posts = $count ? $post_search->fetchSearch(0, $count) : array();
        }

        $posts = $this->hydrateDzenData($posts);
        $posts = $this->filterPostsByAllowedBlogs($posts, $feed_mode === 'separate_by_blog' ? array($blog_id) : $allowed_blog_ids);

        $cache = $this->getFeedCache($plugin, $blog_id, $allowed_blog_ids, $search_options, $limit);
        if ($cache->isCached()) {
            $cached_xml = $cache->get();
            if (is_string($cached_xml) && $cached_xml !== '') {
                while (ob_get_level()) {
                    ob_end_clean();
                }

                header('Content-Type: application/xml; charset=UTF-8');
                echo $cached_xml;
                exit;
            }
        }

        $xml = new XMLWriter();
        $xml->openMemory();
        $xml->startDocument('1.0', 'UTF-8');
        $xml->setIndent(true);

        $xml->startElement('rss');
        $xml->writeAttribute('version', '2.0');
        $xml->writeAttribute('xmlns:content', 'http://purl.org/rss/1.0/modules/content/');

        $channel_link_params = array();
        if ($blog_id) {
            $channel_link_params['blog_url_type'] = $blog_id;
            $channel_link_params['blog_url'] = $available_blogs[$blog_id]['url'];
        }
        $channel_link = wa()->getRouteUrl('blog/frontend', $channel_link_params, true);

        $channel_title = wa()->accountName();
        if ($blog_id && isset($available_blogs[$blog_id])) {
            $channel_title = (string) ifset($available_blogs[$blog_id], 'name', $channel_title);
        }

        $xml->startElement('channel');
        $xml->writeElement('title', $channel_title);
        $xml->writeElement('link', $channel_link);
        $xml->writeElement('description', wa()->accountName());
        $xml->writeElement('language', 'ru');

        foreach ($posts as $post) {
            $params = $this->normalizeParams($post);

            $raw_content = (string) ifset($post, 'text', '');
            $content_html = $this->sanitizeContent($raw_content);
            $first_image = $this->extractFirstImage($raw_content);

            $post_link = $this->makeAbsoluteUrl((string) ifset($post, 'link', ''));
            if ($post_link === '') {
                $post_link = $this->makeAbsoluteUrl((string) blogPost::getUrl($post));
            }
            $fallback_guid = md5((string) ifset($post, 'id', ''));

            $allowed_domains = $this->helper->getAllowedDomains($plugin);
            $enclosure_data = $this->resolveEnclosureUrl($params, $first_image, $plugin, $allowed_domains);
            $enclosure = (string) ifset($enclosure_data, 'url', '');

            $xml->startElement('item');

            $xml->writeElement(
                'title',
                $post['title'] ? $post['title'] : mb_substr(strip_tags($raw_content), 0, 60)
            );

            $xml->writeElement('link', $post_link);

            $guid = trim((string) ifset($params, 'guid', ''));
            if ($guid === '') {
                $guid = $fallback_guid;
            }
            $xml->startElement('guid');
            $xml->writeAttribute('isPermaLink', 'false');
            $xml->text($guid);
            $xml->endElement();

            if ($enclosure) {
                $xml->startElement('enclosure');
                $xml->writeAttribute('url', $enclosure);
                $xml->writeAttribute('type', (string) ifset($enclosure_data, 'type', $this->detectImageMimeType($enclosure)));
                $xml->endElement();
            }

            $description = $this->buildDescription($params, $raw_content);
            if ($description !== '') {
                $xml->writeElement('description', $description);
            }

            $xml->writeElement('pubDate', gmdate('D, d M Y H:i:s +0000', strtotime((string) ifset($post, 'datetime', 'now'))));

            $xml->startElement('content:encoded');
            $xml->writeCData($content_html);
            $xml->endElement();

            $xml->endElement();
        }

        $xml->endElement();
        $xml->endElement();
        $xml->endDocument();

        $output = $xml->outputMemory();
        $cache->set($output);

        while (ob_get_level()) {
            ob_end_clean();
        }

        header('Content-Type: application/xml; charset=UTF-8');
        echo $output;
        exit;
    }


    protected function resolveRequestedBlogId()
    {
        $available_blogs = blogHelper::getAvailable();

        $blog_url_type = $this->getRequest()->param('blog_url_type');
        if (!is_array($blog_url_type)) {
            $blog_url_type = (int) $blog_url_type;
            if ($blog_url_type > 0 && isset($available_blogs[$blog_url_type])) {
                return $blog_url_type;
            }
        }

        $blog_url = trim((string) $this->getRequest()->param('blog_url'));
        if ($blog_url !== '') {
            $resolved_blog_id = $this->resolveBlogIdBySlug($available_blogs, $blog_url);
            if ($resolved_blog_id > 0) {
                return $resolved_blog_id;
            }
        }

        $blog_url_type = $this->getRequest()->param('blog_url_type');
        if (!is_array($blog_url_type)) {
            $blog_url_type = (int) $blog_url_type;
            if ($blog_url_type > 0 && isset($available_blogs[$blog_url_type])) {
                return $blog_url_type;
            }
        }

        $blog_id = $this->getRequest()->param('blog_id');
        if (!is_array($blog_id)) {
            $blog_id = (int) $blog_id;
            if ($blog_id > 0 && isset($available_blogs[$blog_id])) {
                return $blog_id;
            }
        }

        return 0;
    }

    protected function resolveBlogIdBySlug(array $available_blogs, $blog_url)
    {
        $blog_url = trim((string) $blog_url);
        $normalized_blog_url = trim($blog_url, '/');

        foreach ($available_blogs as $id => $blog) {
            $candidate_url = trim((string) ifset($blog, 'url', ''));
            if ($candidate_url === $blog_url || trim($candidate_url, '/') === $normalized_blog_url) {
                return (int) $id;
            }
        }

        return 0;
    }


    protected function resolveEnclosureUrl(array $params, $first_image, blogDzenPlugin $plugin, array $allowed_domains)
    {
        $post_enclosure = $this->makeAbsoluteUrl((string) ifset($params, 'enclosure_url', ''));
        $content_enclosure = $this->makeAbsoluteUrl((string) $first_image);
        $default_enclosure = $this->makeAbsoluteUrl((string) $plugin->getSettings('default_enclosure_url', ''));

        $mode = (string) $plugin->getSettings('default_enclosure_mode', 'added_first');
        if ($mode === 'default_only') {
            return $this->validateEnclosure($default_enclosure, $allowed_domains);
        }

        if ($mode === 'content_first') {
            $candidates = array($content_enclosure, $post_enclosure, $default_enclosure);
        } else {
            $candidates = array($post_enclosure, $content_enclosure, $default_enclosure);
        }

        foreach ($candidates as $candidate) {
            $validated = $this->validateEnclosure($candidate, $allowed_domains);
            if (!empty($validated['url'])) {
                return $validated;
            }
        }

        return array('url' => '', 'type' => '');
    }

    protected function validateEnclosure($url, array $allowed_domains)
    {
        $url = trim((string) $url);
        if ($url === '') {
            return array('url' => '', 'type' => '');
        }

        $validation = $this->helper->validateImageUrl($url, $allowed_domains);
        if (!$validation['ok']) {
            return array('url' => '', 'type' => '');
        }

        return array('url' => $url, 'type' => (string) ifset($validation, 'content_type', 'image/jpeg'));
    }


    protected function filterPostsByAllowedBlogs(array $posts, array $allowed_blog_ids)
    {
        if (!$posts || !$allowed_blog_ids) {
            return array();
        }

        $allowed_map = array_fill_keys(array_map('intval', $allowed_blog_ids), true);
        $filtered_posts = array();

        foreach ($posts as $key => $post) {
            $post_blog_id = (int) ifset($post, 'blog_id', 0);
            $is_disabled_for_dzen = (string) ifset($post, 'dzen', 'publication_mode', '') === 'disabled';
            if (isset($allowed_map[$post_blog_id]) && !$is_disabled_for_dzen) {
                $filtered_posts[$key] = $post;
            }
        }

        return $filtered_posts;
    }

    protected function hydrateDzenData(array $posts)
    {
        $ids = array();
        foreach ($posts as $post) {
            if (!empty($post['id'])) {
                $ids[] = (int) $post['id'];
            }
        }

        $model = new blogDzenPluginPostModel();
        $dzen_data = $model->getByPostIds($ids);

        foreach ($posts as &$post) {
            $post_id = (int) ifset($post, 'id', 0);
            $post['dzen'] = ifset($dzen_data, $post_id, array());
        }
        unset($post);

        return $posts;
    }

    protected function normalizeParams($post)
    {
        $plugin   = waSystem::getInstance()->getPlugin('dzen');
        $defaults = $plugin->getDefaultValues();
        $stored = isset($post['dzen']) && is_array($post['dzen']) ? $post['dzen'] : array();

        foreach ($defaults as $field => $default_value) {
            if (array_key_exists($field, $stored) && $stored[$field] !== null && $stored[$field] !== '') {
                $defaults[$field] = $stored[$field];
            }
        }

        return $defaults;
    }
    protected function buildDescription(array $params, $raw_content)
    {
        $description = trim((string) ifset($params, 'description', ''));
        if ($description !== '') {
            return trim(strip_tags($description));
        }

        $text = (string) $raw_content;
        $text = preg_replace('#<(br|/p|/div|/li|/h[1-6])[^>]*>#i', "\n\n", $text);
        $text = trim(strip_tags($text));
        if ($text === '') {
            return '';
        }

        $text = preg_replace('/\r\n?|\x{2028}|\x{2029}/u', "\n", $text);
        $paragraphs = preg_split('/\n\s*\n+/u', $text, -1, PREG_SPLIT_NO_EMPTY);
        if (!$paragraphs) {
            return '';
        }

        $max_length = 300;
        $result = array();
        $total_length = 0;
        $prev_length = null;

        foreach ($paragraphs as $paragraph) {
            $paragraph = trim(preg_replace('/\s+/u', ' ', (string) $paragraph));
            if ($paragraph === '') {
                continue;
            }

            $paragraph_length = mb_strlen($paragraph);
            if ($prev_length !== null && $paragraph_length > $prev_length) {
                break;
            }

            $candidate_length = $total_length + ($result ? 2 : 0) + $paragraph_length;
            if ($candidate_length > $max_length) {
                break;
            }

            $result[] = $paragraph;
            $total_length = $candidate_length;
            $prev_length = $paragraph_length;
        }

        if (!$result) {
            $first = trim(preg_replace('/\s+/u', ' ', (string) reset($paragraphs)));
            if ($first === '') {
                return '';
            }
            $cut = mb_substr($first, 0, $max_length + 1);
            $cut = preg_replace('/\s+\S*$/u', '', $cut);
            return trim($cut !== '' ? $cut : mb_substr($first, 0, $max_length));
        }

        return implode("\n\n", $result);
    }

    protected function sanitizeContent($content)
    {
        return $this->helper->sanitizeContent($content);
    }

    protected function getFeedCache(blogDzenPlugin $plugin, $blog_id, array $allowed_blog_ids, array $search_options, $limit)
    {
        $key_data = array(
            'blog_id' => (int) $blog_id,
            'allowed_blog_ids' => array_values(array_map('intval', $allowed_blog_ids)),
            'search_options' => $search_options,
            'limit' => (int) $limit,
            'cache_version' => (int) $plugin->getFeedCacheVersion(),
            'domain' => wa()->getRouting()->getDomain(),
            'route_url' => (string) ifset(wa()->getRouting()->getRoute(), 'url', ''),
        );

        $cache_key = 'blog/plugins/dzen/feed/'.md5(json_encode($key_data));
        return new waSerializeCache($cache_key, 86400 * 30, 'blog');
    }
    protected function detectImageMimeType($url)
    {
        $path = parse_url((string) $url, PHP_URL_PATH);
        $ext  = strtolower((string) pathinfo((string) $path, PATHINFO_EXTENSION));

        if ($ext === 'png') {
            return 'image/png';
        }
        if ($ext === 'gif') {
            return 'image/gif';
        }
        return 'image/jpeg';
    }

    protected function extractFirstImage($content)
    {
        if (preg_match('/<img[^>]+src=["\']([^"\']+)["\']/i', $content, $m)) {
            return $this->makeAbsoluteUrl($m[1]);
        }
        return '';
    }

    protected function makeAbsoluteUrl($url)
    {
        $url = trim((string) $url);
        if ($url === '') {
            return '';
        }

        if ($this->isAbsoluteUrl($url)) {
            return $url;
        }

        if (strpos($url, '//') === 0) {
            return 'https:'.$url;
        }

        if (strpos($url, '/') === 0) {
            return $this->base_url.$url;
        }

        if (preg_match('#^[^\s]+\.[^\s]+$#', $url) && strpos($url, '/') === false) {
            return '';
        }

        return $this->base_url.'/'.ltrim($url, '/');
    }

    protected function isAbsoluteUrl($url)
    {
        return (bool) preg_match('#^https?://#i', (string) $url);
    }
}
