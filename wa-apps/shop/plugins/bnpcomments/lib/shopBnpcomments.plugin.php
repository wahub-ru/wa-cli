<?php

/**
 * Created by PhpStorm.
 * User: dimka
 * Date: 21.07.17
 * Time: 2:14
 */
class shopBnpcommentsPlugin extends shopPlugin
{

    public function saveSettings($settings = array()) {

        $settings['state'] = isset($settings['state']) ? 1 : 0;

        parent::saveSettings($settings);
    }

    public function backendOrder($params) {

        if(!$this->getSettings('state')) {
            return;
        }

        $order_id = $params['id'];

        $view = wa()->getView();
        $view->assign('comments', self::getComments($order_id));
        $view->assign('order_id', $order_id);

        return array(
            'info_section' => $view->fetch($this->path . '/templates/hooks/backend_order_info_section.html'),
        );
    }


    public static function getComments($order_id = 0) {

        if(!$order_id) {
            return array();
        }

        $model = new shopBnpcommentsPluginCommentsModel();

        $comments = $model->getOrderComments($order_id);

        $view = wa()->getView();
        $template = wa()->getAppPath('plugins/bnpcomments/templates/comments_list.html', 'shop');

        $view->assign('comments', $comments);
        return $view->fetch($template);
    }

}