<?php

class blogCategoryExtendedBlogPostModel extends blogPostModel
{
    public function getById($id)
    {
        $post = parent::getById($id);

        if (!empty($post['categories'])) {
            $post['categories'] = json_decode($post['categories'], true);
        }

        return $post;
    }
}