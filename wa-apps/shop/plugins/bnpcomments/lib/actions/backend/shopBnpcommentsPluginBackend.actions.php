<?php

/**
 * Created by PhpStorm.
 * User: dimka
 * Date: 21.07.17
 * Time: 2:37
 */
class shopBnpcommentsPluginBackendActions extends waJsonActions
{

    private $model;

    public function __construct() {

        $this->model = new shopBnpcommentsPluginCommentsModel();
    }


    public function addCommentAction() {

        if(!$data = waRequest::post('data', array())) {
            $this->errors[] = 'Нет данных';
            return;
        }

        if(!$data['order_id']) {
            $this->errors[] = 'Не указан id заказа';
            return;
        }

        if(!$data['text']) {
            $this->errors[] = 'Комментарий обязателен';
            return;
        }

        $data['contact_id'] = wa()->getUser()->getId();
        $data['datetime'] = date('Y-m-d H:i:s');

        if(!$this->model->insert($data)) {
            $this->errors[] = 'Что-то пошло не так';
            return;
        }

        $comments = shopBnpcommentsPlugin::getComments($data['order_id']);


        $this->response['comments'] = $comments;
    }

    public function deleteCommentAction() {

        if(!$comment_id = waRequest::post('id', 0)) {
            $this->errors[] = 'Не передан id клмментария';
            return;
        }

        if(!$this->model->deleteById($comment_id)) {
            $this->errors[] = 'Что-то пошло не так';
            return;
        }

        if($order_id = waRequest::post('order_id', 0)) {
            $this->response['count'] = $this->model->countByField('order_id', $order_id);
        }
    }

    public function editCommentAction() {

        if(!$id = waRequest::post('id', 0)) {
            $this->errors[] = 'Не указан id комментария';
            return;
        }

        $text = waRequest::post('text', '');
        $order_id = waRequest::post('order_id', 0);

        if(!$this->model->updateById($id, array(
            'text' => $text,
            'contact_id' => wa()->getUser()->getId(),
            'datetime' => date('Y-m-d H:i:s'),
        ))) {
            $this->errors[] = 'Что-то пошло не так';
            return;
        }

        if($order_id) {
            $this->response['comments'] = shopBnpcommentsPlugin::getComments($order_id);
        }


    }

}