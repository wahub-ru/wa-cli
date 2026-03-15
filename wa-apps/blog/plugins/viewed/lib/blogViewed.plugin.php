<?php

class blogViewedPlugin extends blogPlugin {

    public function frontendPost($post) {
        if (!$this->getSettings('status')) {
            return false;
        }

        $cookie_key = 'post_viewed_' . $post['id'];
        if (!waRequest::cookie($cookie_key)) {
            $post_model = new blogPostModel();
            $post_model->updateById($post['id'], array('viewed' => $post['viewed'] + 1));
            wa()->getResponse()->setCookie($cookie_key, '1', time() + 30 * 86400, null, '', false, true);
        }
    }

}
