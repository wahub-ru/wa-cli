<?php

class shopCatalogmenuPluginActions extends waJsonActions
{
    public function getFullMenuAction()
    {
        try {
            // Получаем экземпляр плагина
            $plugin = wa()->getPlugin('catalogmenu');

            // Вызываем метод получения полного меню
            $menu = $plugin::getFullMenu();

            $this->response = array(
                'status' => 'ok',
                'data' => $menu
            );
        } catch (Exception $e) {
            $this->response = array(
                'status' => 'fail',
                'message' => $e->getMessage()
            );
        }
    }
}