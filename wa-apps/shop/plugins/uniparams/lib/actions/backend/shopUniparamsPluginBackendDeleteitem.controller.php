<?php

class shopUniparamsPluginBackendDeleteitemController extends waJsonController {

    public function execute() {
        $items = new shopUniparamsItemsModel();
        $items_val = new shopUniparamsItemsValsModel();
        $fields = new shopUniparamsListsFieldsModel();
        $data = waRequest::post();

        $item = $items->getById($data['item_id']);
        $this->response['list_id'] = $item['list_id'];

        $contents = $items_val->getByField(array('item_id' => $item['id']), true);

        foreach ($contents as $cont) {
            $tmp_field = $fields->getById($cont['field_id']);
            if ($tmp_field['type'] == 'image') {
                $tmp = preg_split('/\//', $cont['content']);
                if (file_exists(wa()->getDataPath('plugins/uniparams/img/uploaded/', true,
                        'shop').end($tmp)) && end($tmp)) {
                    $path = wa()->getDataPath('plugins/uniparams/img/uploaded/', true,'shop');
                    waFiles::delete($path.end($tmp), true);
                }
            }
        }
        $items_val->deleteByField(array('item_id' => $item['id']));
        $items->deleteById($item['id']);
    }

}