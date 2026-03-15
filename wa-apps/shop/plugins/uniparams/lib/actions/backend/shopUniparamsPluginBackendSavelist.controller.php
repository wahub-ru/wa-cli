<?php

class shopUniparamsPluginBackendSavelistController extends waJsonController {

    public function execute() {
        $model = new shopUniparamsListsModel();
        $model_fields = new shopUniparamsListsFieldsModel();
        $list_params = new shopUniparamsListsParamsModel();
        $data = waRequest::post();

        if (isset($data['list_key']) && $data['list_key'] && !preg_match("/^[a-z0-9\._-]+$/i", $data['list_key'])) {
            $this->errors['id'] = _w('Допускается использовать только латинские буквы, цифры, символ подчеркивания и дефис');
            return;
        }
        $unique = array();
        if (isset($data['keyname']) && $data['keyname']) {
            foreach ($data['keyname'] as $el => $val) {
                if (!preg_match("/^[a-z0-9\._-]+$/i", $val)) {
                    $this->errors['id2'] = _w('Допускается использовать только латинские буквы, цифры, символ подчеркивания и дефис в поле ключ');
                    return;
                }
            }
            foreach($data['keyname'] as $value) {
                if (isset($unique[$value])) {
                    $this->errors['id2'] = _w('Ключ поля должен быть уникальным и не повторятся');
                    return;
                }
                $unique[$value] = 1;
            }
        }

        if (isset($data['param_id']) && $data['param_id'] && !empty($data['param_id'])) {
            foreach ($data['lpkeyname'] as $el => $val) {
                if (!preg_match("/^[a-z0-9\._-]+$/i", $val)) {
                    $this->errors['id3'] = _w('Допускается использовать только латинские буквы, цифры, символ подчеркивания и дефис в поле ключ');
                    return;
                }
                if ($data['lptype'][$el] == 'image') {
                    $file = waRequest::file($val);
                    if ($file->uploaded()) {
                        if (!in_array(strtolower($file->extension), array('png', 'jpg', 'jpeg', 'exif', 'gif', 'bmp'))) {
                            $this->errors['id4'] = _w('Формат файла не принят');
                            return;
                        }
                    }
                }
            }
            $unique = array();
            foreach ($data['lpkeyname'] as $value) {
                if (isset($unique[$value])) {
                    $this->errors['id3'] = _w('Ключ поля должен быть уникальным и не повторятся');
                    return;
                }
                $unique[$value] = 1;
            }
        }

        $model->updateById($data['list_id'], array('name' => $data['title'], 'description' => $data['ldescription'],
            'key_name' => $data['list_key']));

        // edit list's fields
        $fields = $model_fields->getByField(array('list_id' => $data['list_id']), true);
        $old_fields = array();
        foreach ($fields as $field) {
            $old_fields[] = $field['id'];
        }
        if (isset($data['field_id']) && $data['field_id']) {
            foreach ($data['field_id'] as $key => $field_id) {
                if ($field_id == 0) {
                    $model_fields->insert(array('list_id' => $data['list_id'], 'name' => $data['name'][$key],
                        'type' => $data['type'][$key], 'keyname' => $data['keyname'][$key],
                        'description' => $data['description'][$key]));
                } else {
                    if (($key2 = array_search($field_id, $old_fields)) !== false) {
                        unset($old_fields[$key2]);
                    }
                    $model_fields->updateById($field_id, array('list_id' => $data['list_id'], 'name' => $data['name'][$key],
                        'type' => $data['type'][$key], 'keyname' => $data['keyname'][$key],
                        'description' => $data['description'][$key]));
                }
            }
        }
        foreach ($old_fields as $del)
            $model_fields->deleteById($del);

        $params = $list_params->getByField(array('list_id' => $data['list_id']), true);
        $old_params = array();
        foreach ($params as $param) {
            $old_params[] = $param['id'];
        }

        if (isset($data['param_id']) && $data['param_id'] && !empty($data['param_id'])) {
            // edit list's params
            foreach ($data['param_id'] as $key => $param_id) {
                if ($param_id == 0) {
                    if ($data['lptype'][$key] == 'image') {
                        $file = waRequest::file($data['lpkeyname'][$key]);
                        if (!file_exists(wa()->getDataPath('plugins/uniparams/img/uploaded/', true, 'shop'))
                            && !is_dir(wa()->getDataPath('plugins/uniparams/img/uploaded/', true, 'shop'))) {
                            waFiles::create(wa()->getConfig()->getPath('data') . "/public/shop/plugins/uniparams/img/uploaded/",
                                true); 
                        }
                        if ($file) {
                            if ($file->uploaded()) {
                                $file->moveTo(wa()->getConfig()->getPath('data') . "/public/shop/plugins/uniparams/img/uploaded/",
                                    $data['lpkeyname'][$key] . "_" . $data['list_id'] . "." . $file->extension);

                                $dir = wa()->getConfig()->getRootUrl().'wa-data/public/shop/plugins/uniparams/img/uploaded/' . $data['lpkeyname'][$key] . "_" . $data['list_id'] . "." . $file->extension;
                                $list_params->insert(array('list_id' => $data['list_id'], 'content' => $dir,
                                    'type' => $data['lptype'][$key], 'key_name' => $data['lpkeyname'][$key]));
                            }
                        }
                    } else {
                        $list_params->insert(array('list_id' => $data['list_id'], 'content' => $data['lpparam'][$key],
                            'type' => $data['lptype'][$key], 'key_name' => $data['lpkeyname'][$key]));
                    }
                } else {
                    if (($key2 = array_search($param_id, $old_params)) !== false) {
                        unset($old_params[$key2]);
                    }
                    if ($data['lptype'][$key] == 'image') {
                        $file = waRequest::file($data['lpkeyname'][$key]);
                        if (!file_exists(wa()->getDataPath('plugins/uniparams/img/uploaded/', true, 'shop'))
                            && !is_dir(wa()->getDataPath('plugins/uniparams/img/uploaded/', true, 'shop'))) {
                            waFiles::create(wa()->getConfig()->getPath('data') . "/public/shop/plugins/uniparams/img/uploaded/",
                                true); 
                        }
                        if ($file) {
                            if ($file->uploaded()) {
                                // delete first
                                $contents = $list_params->getById($param_id);
                                $tmp = preg_split('/\//', $contents['content']);
                                if (file_exists(wa()->getDataPath('plugins/uniparams/img/uploaded/', true,
                                        'shop') . end($tmp))) {
                                    waFiles::delete(wa()->getDataPath('plugins/uniparams/img/uploaded/', true,
                                            'shop').end($tmp), true);
                                }
                                // add another image
                                $file->moveTo(wa()->getConfig()->getPath('data') . "/public/shop/plugins/uniparams/img/uploaded/",
                                    $data['lpkeyname'][$key] . "_" . $data['list_id'] . "." . $file->extension);

                                $dir = wa()->getConfig()->getRootUrl().'wa-data/public/shop/plugins/uniparams/img/uploaded/' . $data['lpkeyname'][$key] . "_" . $data['list_id'] . "." . $file->extension;
                                $list_params->updateById($param_id, array('content' => $dir, 'type' => $data['lptype'][$key]));
                            }
                        }
                        $list_params->updateById($param_id, array('key_name' => $data['lpkeyname'][$key]));
                    } else {
                        $list_params->updateById($param_id, array('list_id' => $data['list_id'], 'content' => $data['lpparam'][$key],
                            'type' => $data['lptype'][$key], 'key_name' => $data['lpkeyname'][$key]));
                    }
                }
            }
        }
        foreach ($old_params as $del) {
            $item = $list_params->getById($del);
            $tmp = preg_split('/\//', $item['content']);
            if (file_exists(wa()->getDataPath('plugins/uniparams/img/uploaded/', true,
                    'shop') . end($tmp))) {
                waFiles::delete(wa()->getDataPath('plugins/uniparams/img/uploaded/', true,
                        'shop').end($tmp), true);
            }
            $list_params->deleteById($del);
        }

        $this->response['list_id'] = $data['list_id'];
    }

}