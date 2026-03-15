<?php

class blogDzenPluginFrontendValidateAction extends waViewAction
{
    public function execute()
    {
        $post_id = (int) waRequest::request('post_id', 0, waRequest::TYPE_INT);
        if ($post_id <= 0) {
            $this->renderJson(array('error' => 'post_id is required. Save post first.'), 400);
        }

        $post_model = new blogPostModel();
        $post = (array) $post_model->getById($post_id);
        if (!$post) {
            $this->renderJson(array('error' => 'Post not found'), 404);
        }

        $plugin = waSystem::getInstance()->getPlugin('dzen');
        $helper = new blogDzenPluginFeedHelper();
        $validator = new blogDzenPluginValidator($plugin, $helper);

        $payload = $this->buildPayloadFromPost($post);
        $this->renderJson($validator->validate($payload));
    }

    protected function buildPayloadFromPost(array $post)
    {
        $post_id = (int) ifset($post, 'id', 0);
        $dzen_model = new blogDzenPluginPostModel();
        $dzen = $post_id > 0 ? (array) $dzen_model->getByPostId($post_id) : array();

        $post_url = blogPost::getUrl($post);
        if (is_array($post_url)) {
            $post_url = reset($post_url);
        }

        return array(
            'title' => (string) ifset($post, 'title', ''),
            'text' => (string) ifset($post, 'text', ''),
            'link' => (string) $post_url,
            'enclosure_url' => (string) ifset($dzen, 'enclosure_url', ''),
            'short_description' => (string) ifset($dzen, 'description', ''),
        );
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
