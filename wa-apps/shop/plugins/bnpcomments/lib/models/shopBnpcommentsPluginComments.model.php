<?php

/**
 * Created by PhpStorm.
 * User: dimka
 * Date: 21.07.17
 * Time: 2:20
 */
class shopBnpcommentsPluginCommentsModel extends waModel
{
    protected $table = 'shop_bnpcomments';

    public function getOrderComments($order_id = 0) {

        if(!$order_id) {
            return array();
        }

        $sql = 'SELECT comm.*, contacts.name FROM '.$this->table.' comm'
            .' JOIN wa_contact contacts ON contacts.id = comm.contact_id'
            .' WHERE comm.order_id = i:order_id ORDER BY comm.datetime DESC';

        return $this->query($sql, array('order_id' => $order_id))->fetchAll();
    }

}