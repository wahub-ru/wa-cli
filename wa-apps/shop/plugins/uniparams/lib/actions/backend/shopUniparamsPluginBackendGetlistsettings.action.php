<?php

class shopUniparamsPluginBackendGetlistsettingsAction extends waViewAction {

    public function execute() {
        $request = waRequest::post();
        $list_id = $request['list_id'];
        $model = new shopUniparamsListsModel();
        $fields_model = new shopUniparamsListsFieldsModel();
        $params_model = new shopUniparamsListsParamsModel();

        $list_data = $model->getById($list_id);
        $fields = $fields_model->getByField(array('list_id' => $list_id), true);
        $params = $params_model->getByField(array('list_id' => $list_id), true);

        foreach ($params as $key => $param) {
            if ($param['type'] == 'image') {
                $tmp = preg_split('/\//', $param['content']);
                if (!file_exists(wa()->getDataPath('plugins/uniparams/img/uploaded/', true,
                                    'shop').end($tmp)) || !end($tmp)) {
                    $params[$key]['content'] = NULL;
                }
            }
        }

        $this->view->assign('list', $list_data);
        $this->view->assign('params', $params);
        $this->view->assign('fields', $fields);
    }

}