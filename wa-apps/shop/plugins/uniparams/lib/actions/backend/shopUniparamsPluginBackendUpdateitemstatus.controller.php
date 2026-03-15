<?php

class shopUniparamsPluginBackendUpdateitemstatusController extends waJsonController {

    public function execute() {
        $items = new shopUniparamsItemsModel();
        $data = waRequest::post();

        $items->updateById($data['item_id'], array('enabled' => $data['status']));
    }

}