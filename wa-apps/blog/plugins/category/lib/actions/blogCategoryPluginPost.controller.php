<?php

class blogCategoryPluginPostAction extends waViewAction
{
    public function execute()
    {
        // Используем расширенную модель
        $post_model = new blogCategoryExtendedBlogPostModel();

        // Получаем ID поста из параметров запроса
        $post_id = waRequest::get('id', 0, 'int');

        // Получаем пост
        $post = $post_model->getById($post_id);

        // Передаем данные в шаблон
        $this->view->assign('post', $post);
    }
}