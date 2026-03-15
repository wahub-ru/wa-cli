<?php

class blogDzenPluginBackendEditAction extends waViewAction
{
    public function execute()
    {
        $post_id = (int) ifset($this->params, 'post_id', 0);

        $plugin = waSystem::getInstance()->getPlugin('dzen');
        $values = $plugin->getDefaultValues();

        if ($post_id > 0) {
            $post_model = new blogDzenPluginPostModel();
            $stored_values = $post_model->getByPostId($post_id);
            if ($stored_values) {
                foreach ($values as $key => $default_value) {
                    if (array_key_exists($key, $stored_values)) {
                        $values[$key] = (string) $stored_values[$key];
                    }
                }

                if ((string) ifset($stored_values, 'publication_mode', '') === 'disabled') {
                    $values['publish_in_dzen'] = 0;
                    $values['publication_mode'] = '';
                }
            }
        }

        $root_url = rtrim(wa()->getRootUrl(true), '/');

        $this->view->assign('dzen', $values);
        $this->view->assign('site_example_image_url', $root_url.'/wa-data/public/site/data/image.png');
    }
}
