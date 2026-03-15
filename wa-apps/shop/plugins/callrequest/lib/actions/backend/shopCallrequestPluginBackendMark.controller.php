<?php

class shopCallrequestPluginBackendMarkController extends waJsonController
{
    // protected $disableCsrf = true; // включай только если словишь 403 и не хочешь разбираться с токеном

    public function execute()
    {
        if (waRequest::method() !== 'post') {
            $this->errors = 'Method not allowed';
            return;
        }

        $id      = waRequest::post('id', 0, waRequest::TYPE_INT);
        $op      = waRequest::post('op', '', waRequest::TYPE_STRING_TRIM);
        $comment = (string) waRequest::post('comment', '', waRequest::TYPE_STRING_TRIM);

        if ($id <= 0 || $op === '') { $this->errors = 'Bad params'; return; }
        if (!class_exists('shopCallrequestPluginRequestModel')) { $this->errors = 'Model not found'; return; }

        $m = new shopCallrequestPluginRequestModel();
        if (!$m->getById($id)) { $this->errors = 'Not found'; return; }

        switch ($op) {
            case 'done':    $m->updateById($id, ['status' => 'done']);    break;
            case 'delete':  $m->updateById($id, ['status' => 'deleted']); break;
            case 'restore': $m->updateById($id, ['status' => 'new']);     break;
            case 'comment': $m->updateById($id, ['manager_comment' => $comment]); break;
            default: $this->errors = 'Unknown op'; return;
        }

        $this->response = ['ok' => 1, 'id' => (int)$id, 'op' => $op];
    }
}
