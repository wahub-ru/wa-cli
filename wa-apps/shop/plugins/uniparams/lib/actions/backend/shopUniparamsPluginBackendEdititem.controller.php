<?php

class shopUniparamsPluginBackendEdititemController extends waJsonController {

    public function execute() {
        $items = new shopUniparamsItemsModel();
        $items_val = new shopUniparamsItemsValsModel();
        $fields = new shopUniparamsListsFieldsModel();
        $data = waRequest::post();

        $item = $items->getById($data['item_id']);
        $lfields = $fields->getByField(array('list_id' => $item['list_id']), true);
        foreach ($lfields as $field) {
            if ($field['type'] == 'image') {
                $file = waRequest::file($field['id']);
                if ($file->uploaded()) {
                    if (!in_array(strtolower($file->extension), array('png', 'jpg', 'jpeg', 'exif', 'gif', 'bmp'))) {
                        $this->errors['id'] = _w('Формат файла не принят');
                        return;
                    }
                }
            }
        }
        foreach ($lfields as $field) {
            if ($field['type'] == 'image') {
                
                $file = waRequest::file($field['id']);
                
                // check folder
                if (!is_dir(wa()->getDataPath('plugins/uniparams/img/uploaded/', true,'shop'))
                    && !file_exists(wa()->getDataPath('plugins/uniparams/img/uploaded/', true,'shop'))) {
                    waFiles::create(wa()->getConfig()->getPath('data') . "/public/shop/plugins/uniparams/img/uploaded/",
                        true); 
                }
                if ($file->uploaded()) {
                    // delete first
                    $contents = $items_val->getByField(array('item_id' => $data['item_id']), true);
                    foreach ($contents as $cont) {
                        $tmp_field = $fields->getById($cont['field_id']);
                        if ($tmp_field['type'] == 'image') {
                            $tmp = preg_split('/\//', $cont['content']);
                            if (file_exists(wa()->getDataPath('plugins/uniparams/img/uploaded/', true,
                                    'shop').end($tmp)) && end($tmp)) {
                                waFiles::delete(wa()->getDataPath('plugins/uniparams/img/uploaded/', true,
                                        'shop').end($tmp), true); 
                            }
                        }
                    }

                    // add another image
                    $file->moveTo(wa()->getConfig()->getPath('data') .
                        "/public/shop/plugins/uniparams/img/uploaded/",
                        $field['id']."_".$data['item_id'].".".$file->extension);
                    $dir = wa()->getConfig()->getRootUrl().'wa-data/public/shop/plugins/uniparams/img/uploaded/'.$field['id']."_".$data['item_id'].".".$file->extension;

                    $counter = $items_val->getByField(array('field_id' => $field['id'], 'item_id' => $data['item_id']));
                    if (!$counter) {
                        $items_val->insert(array('field_id' => $field['id'], 'item_id' => $data['item_id'],
                            'content' => $dir));
                    } else {
                        $items_val->updateByField(array('field_id' => $field['id'], 'item_id' => $data['item_id']),
                            array('content' => $dir));
                    }
                }
            } else {
                $counter = $items_val->getByField(array('field_id' => $field['id'], 'item_id' => $data['item_id']));
                if (!$counter) {
                    $items_val->insert(array('field_id' => $field['id'], 'item_id' => $data['item_id'],
                        'content' => $data[$field['id']]));
                } else {
                    $items_val->updateByField(array('field_id' => $field['id'], 'item_id' => $data['item_id']),
                        array('content' => $data[$field['id']]));
                }
            }
        }
        $this->response['list_id'] = $item['list_id'];
    }

}