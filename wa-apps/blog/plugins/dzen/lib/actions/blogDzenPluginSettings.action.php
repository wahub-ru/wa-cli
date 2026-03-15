<?php

class blogDzenPluginSettingsAction extends blogPluginsSettingsViewAction
{
    public function execute()
    {
        $this->plugin_id = 'dzen';
        parent::execute();

        $this->getResponse()
            ->addCss('plugins/dzen/css/backend.css?'.wa()->getVersion(), true)
            ->addJs('plugins/dzen/js/settings.js?'.wa()->getVersion(), true);

        $this->saveSettings();

        $settings = $this->plugin_instance->getSettings();
        $this->view->assign('settings', array_merge(array(
            'feed_posts_limit'           => 0,
            'default_enclosure_url'      => '',
            'api_token'                  => '',
            'api_token_created_at'       => '',
            'api_token_expires_at'       => '',
            'feed_mode'                  => 'all_in_one',
            'feed_blog_ids'              => '',
            'default_enclosure_mode'     => 'added_first',
            'feed_cache_version'         => 1,
        ), $settings));



        $all_blogs = blogHelper::getAvailable();
        $selected_blog_ids = preg_split('/\s*,\s*/', (string) ifset($settings, 'feed_blog_ids', ''), -1, PREG_SPLIT_NO_EMPTY);
        $selected_blog_ids = array_map('intval', $selected_blog_ids);
        $selected_blog_ids = array_values(array_filter($selected_blog_ids));

        $feed_mode = (string) ifset($settings, 'feed_mode', 'all_in_one');

        $feed_blogs = array();
        foreach ($selected_blog_ids as $selected_blog_id) {
            if (isset($all_blogs[$selected_blog_id])) {
                $feed_blogs[$selected_blog_id] = $all_blogs[$selected_blog_id];
            }
        }

        $root_url = rtrim(wa()->getRootUrl(true), '/');
        $feed_url_all_in_one = $this->buildAllInOneUrl('rss-dzen/');

        $feed_urls_by_blog = array();
        foreach ($all_blogs as $feed_blog_id => $feed_blog) {
            $feed_urls_by_blog[$feed_blog_id] = $this->buildBlogSpecificUrl('rss-dzen/', $feed_blog);
        }

        $feed_url_examples = array();
        if ($feed_mode === 'all_in_one') {
            if ($feed_url_all_in_one !== '') {
                $feed_url_examples[] = $feed_url_all_in_one;
            }
        } else {
            foreach ($feed_blogs as $feed_blog_id => $feed_blog) {
                if (!empty($feed_urls_by_blog[$feed_blog_id])) {
                    $feed_url_examples[] = $feed_urls_by_blog[$feed_blog_id];
                }
            }
        }

        $feed_url_examples = array_values(array_unique(array_filter(array_map('trim', $feed_url_examples))));


        $blog_post_stats = $this->getBlogPostStats($all_blogs);
        $feed_stats_payload = array(
            'all_in_one' => $this->buildFeedStatSummary(array_keys($all_blogs), $blog_post_stats),
            'by_blog' => array(),
        );
        foreach ($all_blogs as $feed_blog_id => $feed_blog) {
            $feed_stats_payload['by_blog'][$feed_blog_id] = $this->buildFeedStatSummary(array($feed_blog_id), $blog_post_stats);
        }

        $this->view->assign('all_blogs', $all_blogs);
        $this->view->assign('selected_blog_ids', $selected_blog_ids);
        $this->view->assign('feed_url_examples', $feed_url_examples);
        $this->view->assign('feed_url_all_in_one', $feed_url_all_in_one);
        $this->view->assign('feed_urls_by_blog', $feed_urls_by_blog);
        $this->view->assign('feed_stats_payload_json', json_encode($feed_stats_payload));
        $has_available_feed_urls = (bool) $feed_url_all_in_one || (bool) array_filter($feed_urls_by_blog);

        $this->view->assign('has_available_feed_urls', $has_available_feed_urls);
        $this->view->assign('api_url_base', $this->buildAllInOneUrl('dzen-api/save/'));
        $this->view->assign('site_example_image_url', $root_url.'/wa-data/public/site/data/image.png');
    }


    protected function buildAllInOneUrl($suffix)
    {
        $rss_url = blogHelper::getUrl(null, 'blog/frontend/rss', array(), true);
        if (is_array($rss_url)) {
            $rss_url = reset($rss_url);
        }

        $rss_url = trim((string) $rss_url);
        if ($rss_url === '') {
            return '';
        }

        $rss_url = rtrim($rss_url, '/');
        return preg_replace('~/rss$~', '/'.trim((string) $suffix, '/'), $rss_url).'/';
    }


    protected function buildBlogSpecificUrl($suffix, array $blog)
    {
        $params = array();
        $blog_id = (int) ifset($blog, 'id', 0);
        $blog_url = trim((string) ifset($blog, 'url', ''));
        if ($blog_url !== '') {
            $params['blog_url'] = $blog_url;
        }

        $base_url = blogHelper::getUrl($blog_id > 0 ? $blog_id : null, 'blog/frontend', $params, true);
        if (is_array($base_url)) {
            $base_url = $this->resolvePreferredBlogUrl($base_url, $blog_url);
        }

        $base_url = trim((string) $base_url);
        if ($base_url === '') {
            return '';
        }

        return rtrim((string) $base_url, '/').'/'.ltrim((string) $suffix, '/');
    }

    protected function resolvePreferredBlogUrl(array $urls, $blog_url = '')
    {
        $urls = array_values(array_filter(array_map('trim', $urls)));
        if (!$urls) {
            return '';
        }

        $blog_url = trim((string) $blog_url, '/');
        if ($blog_url !== '') {
            foreach ($urls as $url) {
                if (strpos($url, '/'.$blog_url.'/') !== false) {
                    return $url;
                }
            }
        }

        return reset($urls);
    }


    protected function getBlogPostStats(array $all_blogs)
    {
        $stats = array();
        foreach (array_keys($all_blogs) as $blog_id) {
            $stats[(int) $blog_id] = array('total' => 0, 'recent' => 0);
        }

        if (!$stats) {
            return $stats;
        }

        $post_model = new blogPostModel();
        $status_published = blogPostModel::STATUS_PUBLISHED;
        $month_ago = date('Y-m-d H:i:s', strtotime('-1 month'));

        $total_rows = $post_model->query(
            "SELECT blog_id, COUNT(*) AS cnt FROM blog_post WHERE status = '".$post_model->escape($status_published)."' GROUP BY blog_id"
        )->fetchAll();
        foreach ($total_rows as $row) {
            $blog_id = (int) ifset($row, 'blog_id', 0);
            if (isset($stats[$blog_id])) {
                $stats[$blog_id]['total'] = (int) ifset($row, 'cnt', 0);
            }
        }

        $recent_rows = $post_model->query(
            "SELECT blog_id, COUNT(*) AS cnt FROM blog_post WHERE status = '".$post_model->escape($status_published)."' AND datetime >= '".$post_model->escape($month_ago)."' GROUP BY blog_id"
        )->fetchAll();
        foreach ($recent_rows as $row) {
            $blog_id = (int) ifset($row, 'blog_id', 0);
            if (isset($stats[$blog_id])) {
                $stats[$blog_id]['recent'] = (int) ifset($row, 'cnt', 0);
            }
        }

        return $stats;
    }

    protected function buildFeedStatSummary(array $blog_ids, array $blog_post_stats)
    {
        $total = 0;
        $recent = 0;

        foreach ($blog_ids as $blog_id) {
            $blog_id = (int) $blog_id;
            $total += (int) ifset($blog_post_stats, $blog_id, 'total', 0);
            $recent += (int) ifset($blog_post_stats, $blog_id, 'recent', 0);
        }

        $missing_total = max(0, 10 - $total);
        $missing_recent = max(0, 3 - $recent);

        if ($total <= 0) {
            $level = 'error';
            $message = 'Фид не готов, нет записей.';
        } elseif ($missing_total === 0 && $missing_recent === 0) {
            $level = 'success';
            $message = 'Все в порядке, можно публиковать.';
        } else {
            $level = 'warning';
            $parts = array();
            if ($missing_recent > 0) {
                $parts[] = 'Добавьте ещё '.$missing_recent.' '.$this->pluralRu($missing_recent, 'публикацию', 'публикации', 'публикаций').' за последний месяц (сейчас '.$recent.')';
            }
            if ($missing_total > 0) {
                $parts[] = 'в фиде '.$total.' '.$this->pluralRu($total, 'материал', 'материала', 'материалов').', требуется минимум 10';
            }
            $message = implode('; ', $parts).'.';
        }

        return array(
            'total' => $total,
            'recent' => $recent,
            'required_total' => 10,
            'required_recent' => 3,
            'level' => $level,
            'message' => $message,
        );
    }


    protected function pluralRu($number, $one, $few, $many)
    {
        $number = abs((int) $number);
        $n10 = $number % 10;
        $n100 = $number % 100;

        if ($n100 >= 11 && $n100 <= 14) {
            return $many;
        }
        if ($n10 === 1) {
            return $one;
        }
        if ($n10 >= 2 && $n10 <= 4) {
            return $few;
        }

        return $many;
    }

    protected function saveSettings()
    {
        $settings = $this->getRequest()->post('settings');
        if (empty($settings) || !is_array($settings)) {
            return;
        }

        $clear_feed_cache = (int) ifset($settings, 'clear_feed_cache', 0) === 1;

        $settings['feed_posts_limit'] = trim((string) ifset($settings, 'feed_posts_limit', ''));
        $settings['feed_posts_limit'] = (int) $settings['feed_posts_limit'];
        if ($settings['feed_posts_limit'] < 0) {
            $settings['feed_posts_limit'] = 0;
        }

        if ((int) ifset($settings, 'default_enclosure_delete', 0) === 1) {
            $settings['default_enclosure_url'] = '';
        }

        $settings['feed_mode'] = ifset($settings, 'feed_mode', 'all_in_one') === 'separate_by_blog' ? 'separate_by_blog' : 'all_in_one';

        $feed_blog_ids = ifset($settings, 'feed_blog_ids', array());
        if (!is_array($feed_blog_ids)) {
            $feed_blog_ids = array();
        }
        $feed_blog_ids = array_values(array_unique(array_filter(array_map('intval', (array) $feed_blog_ids))));
        $settings['feed_blog_ids'] = implode(',', $feed_blog_ids);

        $settings['default_enclosure_mode'] = (string) ifset($settings, 'default_enclosure_mode', 'added_first');
        if (!in_array($settings['default_enclosure_mode'], array('added_first', 'content_first', 'default_only'), true)) {
            $settings['default_enclosure_mode'] = 'added_first';
        }

        $token_action = ifset($settings, 'api_token_action', 'keep');
        if (!in_array($token_action, array('keep', 'create', 'recreate', 'delete'), true)) {
            $token_action = 'keep';
        }

        if ($token_action === 'create' || $token_action === 'recreate') {
            $settings['api_token'] = md5(uniqid('dzen', true));
            $settings['api_token_created_at'] = date('Y-m-d H:i:s');
            $settings['api_token_expires_at'] = date('Y-m-d H:i:s', strtotime('+365 days'));
        } elseif ($token_action === 'delete') {
            $settings['api_token'] = '';
            $settings['api_token_created_at'] = '';
            $settings['api_token_expires_at'] = '';
        }

        $allowed = array(
            'feed_posts_limit',
            'default_enclosure_url',
            'api_token',
            'api_token_created_at',
            'api_token_expires_at',
            'feed_mode',
            'feed_blog_ids',
            'default_enclosure_mode',
        );

        $result = array();
        foreach ($allowed as $k) {
            if (isset($settings[$k])) {
                $result[$k] = is_string($settings[$k]) ? trim($settings[$k]) : $settings[$k];
            }
        }

        $this->plugin_instance->saveSettings($result);

        if (($clear_feed_cache || !empty($result)) && method_exists($this->plugin_instance, 'bumpFeedCacheVersion')) {
            $this->plugin_instance->bumpFeedCacheVersion();
        }
    }
}
