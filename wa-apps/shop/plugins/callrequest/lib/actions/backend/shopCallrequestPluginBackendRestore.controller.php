<?php

class shopCallrequestPluginBackendRestoreController extends waJsonController
{
    public function execute()
    {
        $id = waRequest::post('id', 0, waRequest::TYPE_INT);
        if ($id <= 0) {
            $this->errors = array('message' => 'Некорректный ID');
            return;
        }
        $m = new shopCallrequestPluginRequestModel();
        $m->updateById($id, array('status' => 'new'));
        $this->response = array('ok' => 1);
    }
}
