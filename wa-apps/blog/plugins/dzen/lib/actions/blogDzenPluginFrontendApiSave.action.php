<?php

class blogDzenPluginFrontendApiSaveAction extends waViewAction
{
    public function execute()
    {
        $request = waRequest::method();
        if (!in_array($request, array('post', 'put'), true)) {
            $this->renderJson(array('error' => 'Method not allowed'), 405);
            return;
        }

        $plugin = waSystem::getInstance()->getPlugin('dzen');
        $token = trim((string) waRequest::request('token', '', waRequest::TYPE_STRING_TRIM));
        $saved_token = trim((string) $plugin->getSettings('api_token', ''));

        if ($token === '' || $saved_token === '' || !hash_equals($saved_token, $token)) {
            $this->renderJson(array('error' => 'Invalid token'), 403);
            return;
        }

        $raw = file_get_contents('php://input');
        $json = json_decode((string) $raw, true);
        if (!is_array($json)) {
            $json = array();
        }

        $post_id = (int) ifset($json, 'post_id', waRequest::post('post_id', 0, waRequest::TYPE_INT));
        if ($post_id <= 0) {
            $this->renderJson(array('error' => 'post_id is required'), 400);
            return;
        }

        $data = ifset($json, 'dzen', array());
        if (!is_array($data)) {
            $data = waRequest::post('dzen', array(), waRequest::TYPE_ARRAY);
        }

        $post_model = new blogPostModel();
        $post = $post_model->getById($post_id);
        if (!$post) {
            $this->renderJson(array('error' => 'Post not found'), 404);
            return;
        }

        $plugin->savePostData($post, $data);
        if (method_exists($plugin, 'bumpFeedCacheVersion')) {
            $plugin->bumpFeedCacheVersion();
        }

        $model = new blogDzenPluginPostModel();
        $saved = $model->getByPostId($post_id);

        $this->renderJson(array(
            'status'  => 'ok',
            'post_id' => $post_id,
            'dzen'    => $saved ? $saved : array(),
        ));
    }

    protected function renderJson(array $data, $code = 200)
    {
        while (ob_get_level()) {
            ob_end_clean();
        }

        wa()->getResponse()->setStatus($code);
        header('Content-Type: application/json; charset=UTF-8');
        echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        exit;
    }
}
