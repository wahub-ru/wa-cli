<?php

class shopUniparamsPluginBackendAdditemController extends waJsonController {

    public function execute() {
        $items = new shopUniparamsItemsModel();
        $items_val = new shopUniparamsItemsValsModel();
        $fields = new shopUniparamsListsFieldsModel();
        $data = waRequest::post();

        $lfields = $fields->getByField(array('list_id' => $data['list_id']), true);
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

        $items_temp = $items->getByField('list_id', $data['list_id'], true);
        if (empty($items_temp)) {
            $ret = $items->insert(array('list_id' => $data['list_id'], 'front_index' => 0, 'enabled' => 1));
        }
        else {
            $max_index = $items->query('SELECT MAX(front_index) AS max_index FROM shop_uniparams_items WHERE list_id=i:list_id', array('list_id' => $data['list_id']))->fetchAll();
            $ret = $items->insert(array('list_id' => $data['list_id'], 'enabled' => 1,
                'front_index' => $max_index[0]['max_index']+1));
        }

        foreach ($lfields as $field) {
            if ($field['type'] == 'image') {

                $file = waRequest::file($field['id']);

                if (!file_exists(wa()->getConfig()->getPath('data') . "/public/shop/plugin/uniparams/img/uploaded/")
                    && !is_dir(wa()->getConfig()->getPath('data') . "/public/shop/plugin/uniparams/img/uploaded/")) {
                    waFiles::create(wa()->getConfig()->getPath('data') . "/public/shop/plugins/uniparams/img/uploaded/",
                        true); 
                }
                if ($file->uploaded()) {
                    $file->moveTo(wa()->getConfig()->getPath('data') . "/public/shop/plugins/uniparams/img/uploaded/",
                        $field['id']."_".$ret.".".$file->extension);
                }
                $dir = wa()->getConfig()->getRootUrl().'wa-data/public/shop/plugins/uniparams/img/uploaded/'.$field['id']."_".$ret.".".$file->extension;

                $items_val->insert(array('field_id' => $field['id'], 'item_id' => $ret, 'content' => $dir));
            } else {
                $items_val->insert(array('field_id' => $field['id'], 'item_id' => $ret, 'content' => $data[$field['id']]));
            }
        }
        $this->response['list_id'] = $data['list_id'];
    }

}