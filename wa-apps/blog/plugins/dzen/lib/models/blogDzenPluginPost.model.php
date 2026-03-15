<?php

class blogDzenPluginPostModel extends waModel
{
    protected $table = 'blog_dzen_post';

    public function getByPostId($post_id)
    {
        return $this->getByField('post_id', (int) $post_id);
    }

    public function getByPostIds(array $post_ids)
    {
        $post_ids = array_values(array_unique(array_map('intval', $post_ids)));
        if (!$post_ids) {
            return array();
        }

        return $this->getByField('post_id', $post_ids, 'post_id');
    }

    public function saveByPostId($post_id, array $data)
    {
        $post_id = (int) $post_id;
        if ($post_id <= 0) {
            return;
        }

        $row = array_merge($data, array('post_id' => $post_id));
        $exists = $this->select('post_id')->where('post_id = i:post_id', array('post_id' => $post_id))->fetchField('post_id');

        if ($exists) {
            $this->updateByField('post_id', $post_id, $row);
        } else {
            $this->insert($row);
        }
    }

    public function deleteByPostId($post_id)
    {
        $post_id = (int) $post_id;
        if ($post_id > 0) {
            $this->deleteByField('post_id', $post_id);
        }
    }

    public function deleteByPostIds($post_ids)
    {
        if (!is_array($post_ids)) {
            $post_ids = array($post_ids);
        }

        $post_ids = array_values(array_unique(array_filter(array_map('intval', $post_ids))));
        if ($post_ids) {
            $this->deleteByField('post_id', $post_ids);
        }
    }
}

