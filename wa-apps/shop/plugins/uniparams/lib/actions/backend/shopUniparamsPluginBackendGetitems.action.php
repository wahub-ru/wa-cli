<?php

class shopUniparamsPluginBackendGetitemsAction extends waViewAction {

    public function execute() {
        $request = waRequest::get();
        $list_id = $request['list_id'];
        $itemsModel = new shopUniparamsItemsModel();
        $itemsvModel = new shopUniparamsItemsValsModel();
        $listFields = new shopUniparamsListsFieldsModel();

        $error = null;
        if ($list_id && is_numeric($list_id)) { 
            $items = $itemsModel->query("SELECT * FROM shop_uniparams_items WHERE list_id=i:list_id ORDER BY front_index ASC", array('list_id' => $list_id))->fetchAll();
            $fields = $listFields->getByField(array("list_id" => $list_id), true);

            foreach ($items as $key => $item) {
                $vals = array();
                $itemsv = $itemsvModel->getByField(array('item_id' => $item['id']), true);
                foreach ($itemsv as $key2 => $val) {
                    $vals[$val['field_id']] = $val['content'];
                }
                $items[$key]['vals'] = $vals;
            }
        } else {
            $items = array();
            $fields = array();
            $error = "Создайте список";
        }

        foreach ($items as $key1 => $item) {
            foreach ($fields as $key2 => $field) {
                if ($field['type'] == 'image') {
                    $tmp = preg_split('/\//', $item['vals'][$field['id']]);
                    if (!file_exists(wa()->getDataPath('plugins/uniparams/img/uploaded/', true,
                                        'shop').end($tmp)) || !end($tmp)) {
                        $items[$key1]['vals'][$field['id']] = NULL;
                    }
                }
            }
        }

        $this->view->assign('error', $error);
        $this->view->assign('items', $items);
        $this->view->assign('field_types', $fields);

    }

}