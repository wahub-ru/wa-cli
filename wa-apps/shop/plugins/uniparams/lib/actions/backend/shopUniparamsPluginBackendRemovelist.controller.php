<?php

class shopUniparamsPluginBackendRemovelistController extends waJsonController {

    public function execute() {
        $model = new shopUniparamsListsModel();
        $model_fields = new shopUniparamsListsFieldsModel();
        $items = new shopUniparamsItemsModel();
        $items_val = new shopUniparamsItemsValsModel();
        $list_params = new shopUniparamsListsParamsModel();
        $data = waRequest::post();


        $items_old = $items->getByField(array("list_id" => $data['list_id']), true);
        foreach ($items_old as $item) {
            $contents = $items_val->getByField(array('item_id' => $item['id']), true);
            foreach ($contents as $cont) {
                $tmp_field = $model_fields->getById($cont['field_id']);
                if ($tmp_field['type'] == 'image') {
                    $tmp = preg_split('/\//', $cont['content']);
                    if (file_exists(wa()->getDataPath('plugins/uniparams/img/uploaded/', true,
                            'shop').end($tmp)) && end($tmp)) {
                        waFiles::delete(wa()->getDataPath('plugins/uniparams/img/uploaded/', true,
                                'shop').end($tmp), true); 
                    }
                }
            }
            $items_val->deleteByField(array('item_id' => $item['id']));
            $items->deleteById($item['id']);
        }
        $items_old = $list_params->getByField(array('list_id' => $data['list_id']), true); // old params
        foreach ($items_old as $item) {
            if ($item['type'] == 'image') {
                $tmp = preg_split('/\//', $item['content']);
                if (file_exists(wa()->getDataPath('plugins/uniparams/img/uploaded/', true,
                        'shop').end($tmp)) && end($tmp)) {
                    waFiles::delete(wa()->getDataPath('plugins/uniparams/img/uploaded/', true,
                            'shop').end($tmp), true); 
                }
            }
            $list_params->deleteById($item['id']);
        }

        $model->deleteById($data['list_id']);
        $model_fields->deleteByField('list_id', $data['list_id']);
    }

}