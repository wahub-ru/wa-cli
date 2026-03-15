<?php

/**
 * @author Nikolay Ivanov <megazubr@gmail.com>
 */
class blogStekblg1PluginBackendRemoveController extends waJsonController {
    public function execute() {
        if (waRequest::post('remove')){
            $model = new blogCommentModel();
            try{
                $model->deleteByField('status', 'deleted');
            } catch (waDbException $e) {
                $this->setError('Ошибка удаления данных', $e);
            }
        }
        if (!$this->errors){
            $this->response['status'] = 'ok';
        }
    }
}
