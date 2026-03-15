<?php

class shopGetimagePluginBackendAction extends waViewAction
{
    public function execute()
    {
        // Логика вашего контроллера
        $this->view->assign('message', 'Hello from My Plugin!');

        // Указываем шаблон для отображения
        $this->setTemplate('Backend.html');
    }
}