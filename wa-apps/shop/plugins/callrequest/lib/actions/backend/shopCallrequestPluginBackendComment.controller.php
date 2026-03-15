<?php

class shopCallrequestPluginBackendCommentController extends waJsonController
{
    public function execute()
    {
        $id = waRequest::post('id', 0, waRequest::TYPE_INT);
        $comment = trim(waRequest::post('comment', '', waRequest::TYPE_STRING_TRIM));

        if ($id <= 0) {
            $this->errors = array('message' => 'Некорректный ID');
            return;
        }
        $m = new shopCallrequestPluginRequestModel();
        $m->updateById($id, array('manager_comment' => $comment));
        $this->response = array('ok' => 1);
    }
}
