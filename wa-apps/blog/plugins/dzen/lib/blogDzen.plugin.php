<?php

class blogDzenPlugin extends blogPlugin
{
    const PARAM_PREFIX = 'dzen_';

    public function backendPostEdit($post)
    {
        $output = array();

        $action = new blogDzenPluginBackendEditAction(array(
            'post_id'    => (int) ifset($post, 'id', 0),
            'blog_id'    => (int) ifset($post, 'blog_id', 0),
            'contact_id' => (int) ifset($post, 'contact_id', 0),
        ));

        $this->addJs('js/backend.js', true);
        $this->addCss('css/backend.css', true);
        $output['toolbar'] = $action->display(false);

        return $output;
    }

    public function postSave($post)
    {
        if (waConfig::get('is_template')) {
            return;
        }

        if (empty($post['id'])) {
            return;
        }

        $plugin_data = ifset($post, 'plugin', $this->id, null);
        if (!is_array($plugin_data)) {
            $request_plugin = waRequest::post('plugin', array(), waRequest::TYPE_ARRAY);
            $plugin_data = ifset($request_plugin, $this->id, null);
        }
        if (!is_array($plugin_data)) {
            return;
        }

        $this->savePostData($post, $plugin_data);
        $this->bumpFeedCacheVersion();
    }


    public function postDelete($post_ids)
    {
        if (waConfig::get('is_template')) {
            return;
        }

        $model = new blogDzenPluginPostModel();
        $rows = $model->getByField('post_id', is_array($post_ids) ? $post_ids : array($post_ids), true);
        foreach ($rows as $row) {
            $this->deleteUploadedFileByUrl(ifset($row, 'enclosure_url', ''));
        }
        $model->deleteByPostIds($post_ids);
        $this->bumpFeedCacheVersion();
    }

    public function savePostData(array $post, array $plugin_data)
    {
        if (waConfig::get('is_template')) {
            return;
        }

        $post_id = (int) ifset($post, 'id', 0);
        if ($post_id <= 0) {
            return;
        }

        $model = new blogDzenPluginPostModel();
        $existing = $model->getByPostId($post_id);

        $publish_in_dzen = (int) ifset($plugin_data, 'publish_in_dzen', 1) === 1;
        if (!$publish_in_dzen) {
            $plugin_data['publication_mode'] = 'disabled';
        } elseif ((string) ifset($plugin_data, 'publication_mode', '') === 'disabled') {
            $plugin_data['publication_mode'] = '';
        }

        $guid = $this->getScalarValue(ifset($plugin_data, 'guid', ''));
        $pdalink = $this->getScalarValue(ifset($plugin_data, 'pdalink', ''));

        $post_link = '';
        if (!empty($post['url'])) {
            $post_link = $this->getScalarValue(blogPost::getUrl($post));
        }
        if ($post_link === '') {
            $post_link = 'blog-post-'.$post_id;
        }

        if ($guid === '') {
            $guid = md5((string) $post_id);
            $plugin_data['guid'] = $guid;
        }
        if ($pdalink === '') {
            $plugin_data['pdalink'] = $post_link;
        }

        if ((int) ifset($plugin_data, 'enclosure_delete', 0) === 1) {
            $this->deleteUploadedFileByUrl(ifset($existing, 'enclosure_url', ''));
            $plugin_data['enclosure_url'] = '';
        }

        $row = array();
        foreach ($this->getAllowedFields() as $field) {
            $row[$field] = $this->getScalarValue(ifset($plugin_data, $field, ''));
        }

        $model->saveByPostId($post_id, $row);
    }

    public function getFeedCacheVersion()
    {
        return (int) $this->getSettings('feed_cache_version', 1);
    }

    public function bumpFeedCacheVersion()
    {
        $version = $this->getFeedCacheVersion() + 1;
        $this->saveSettings(array('feed_cache_version' => $version));
        return $version;
    }

    public function getAllowedFields()
    {
        return array(
            'publication_mode',
            'guid',
            'pdalink',
            'description',
            'enclosure_url',
        );
    }

    protected function deleteUploadedFileByUrl($url)
    {
        $url = trim((string) $url);
        if ($url === '') {
            return;
        }

        $parsed_path = parse_url($url, PHP_URL_PATH);
        if (!$parsed_path) {
            return;
        }

        $data_url = wa()->getDataUrl('plugins/dzen/', true, 'blog', true);
        $data_path = wa()->getDataPath('plugins/dzen/', false, 'blog', true);
        $data_url_path = parse_url($data_url, PHP_URL_PATH);

        if (!$data_url_path || strpos($parsed_path, $data_url_path) !== 0) {
            return;
        }

        $relative_path = ltrim(substr($parsed_path, strlen($data_url_path)), '/');
        if ($relative_path === '' || strpos($relative_path, '..') !== false) {
            return;
        }

        $absolute_path = rtrim($data_path, '/').'/'.$relative_path;
        if (is_file($absolute_path)) {
            waFiles::delete($absolute_path);
        }
    }

    protected function getScalarValue($value)
    {
        while (is_array($value)) {
            if (!$value) {
                return '';
            }

            $value = reset($value);
        }

        if ($value === null || is_object($value)) {
            return '';
        }

        return trim((string) $value);
    }


    public function routing($route = array())
    {
        $blog_id = isset($route['blog_url_type']) ? (int) $route['blog_url_type'] : 0;

        switch ($blog_id) {
            case -1:
                $route_id = 2;
                break;
            case 0:
                $route_id = 0;
                break;
            default:
                $route_id = 1;
                break;
        }

        $file = $this->path.'/lib/config/routing.php';
        if (file_exists($file)) {
            $routing = include($file);
            if (isset($routing[$route_id])) {
                return $routing[$route_id];
            }
        }

        return array();
    }

    public function getDefaultValues()
    {
        return array(
            'publish_in_dzen'  => 1,
            'publication_mode' => '',
            'guid'               => '',
            'pdalink'            => '',
            'description'        => '',
            'enclosure_url'      => '',
        );
    }
}
