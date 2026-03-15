<?php

class shopUniparamsPluginBackendAddlistController extends waJsonController {

    public function execute() {
        $model = new shopUniparamsListsModel();
        $model_fields = new shopUniparamsListsFieldsModel();
        $list_params = new shopUniparamsListsParamsModel();
        $data = waRequest::post();

        if (!preg_match("/^[a-z0-9\._-]+$/i", $data['list_key'])) {
            $this->errors['id'] = _w('Допускается использовать только латинские буквы, цифры, символ подчеркивания и дефис');
            return;
        }
        $exists = $model->getByField(array('key_name' => $data['list_key']), true);
        if (!empty($exists)) {
            $this->errors['id'] = _w('Спискок с таким ключом уже существует');
            return;
        }
        if (isset($data['name']) && $data['name'] && !empty($data['name'])) {
            foreach ($data['keyname'] as $el => $val) {
                if (!preg_match("/^[a-z0-9\._-]+$/i", $val)) {
                    $this->errors['id2'] = _w('Допускается использовать только латинские буквы, цифры, символ подчеркивания и дефис в поле ключ');
                    return;
                }
            }
        }
        $unique = array();
        if (isset($data['name']) && $data['name'] && !empty($data['name'])) {
            foreach ($data['keyname'] as $value) {
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
                    if (!in_array(strtolower($file->extension), array('png', 'jpg', 'jpeg', 'exif', 'gif', 'bmp'))) {
                        $this->errors['id4'] = _w('Формат файла не принят');
                        return;
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

        if (isset($data['param_id']) && $data['param_id'] && !empty($data['param_id'])) {
            foreach ($data['lpkeyname'] as $ind => $val) {
                if ($data['lptype'][$ind] == 'image') {
                    $file = waRequest::file($val);
                    if ($file->uploaded()) {
                        if (!in_array(strtolower($file->extension), array('png', 'jpg', 'jpeg', 'exif', 'gif', 'bmp'))) {
                            $this->errors['id'] = _w('Формат файла не принят');
                            return;
                        }
                    }
                }
            }
        }

        $lists2 = $model->getAll();
        if (empty($lists2)) {
            $ret = $model->insert(array('name' => $data['title'], 'key_name' => $data['list_key'],
                'description' => $data['ldescription'], 'front_index' => 0));
        } else {
            $max_index = $model->query('SELECT MAX(front_index) AS max_index FROM shop_uniparams_lists')->fetchAll();
            $ret = $model->insert(array('name' => $data['title'], 'key_name' => $data['list_key'],
                'description' => $data['ldescription'], 'front_index' => $max_index[0]['max_index']+1));
        }
        if (isset($data['name']) && $data['name'] && !empty($data['name'])) {
            foreach ($data['name'] as $ind => $val) {
                $model_fields->insert(array('list_id' => $ret, 'name' => $val,
                    'type' => $data['type'][$ind], 'keyname' => $data['keyname'][$ind],
                    'description' => $data['description'][$ind]));
            }
        }

        if (isset($data['param_id']) && $data['param_id'] && !empty($data['param_id'])) {
            foreach ($data['lpkeyname'] as $ind => $val) {
                if ($data['lptype'][$ind] == 'image') {
                    $file = waRequest::file($val);
                    if (!file_exists(wa()->getConfig()->getPath('data') . "/public/shop/plugin/uniparams/img/uploaded/")
                        && !is_dir(wa()->getConfig()->getPath('data') . "/public/shop/plugin/uniparams/img/uploaded/")) {
                        waFiles::create(wa()->getConfig()->getPath('data') . "/public/shop/plugins/uniparams/img/uploaded/",
                            true);
                    }
                    if ($file->uploaded()) {
                        $file->moveTo(wa()->getConfig()->getPath('data') . "/public/shop/plugins/uniparams/img/uploaded/",
                            $data['lpkeyname'][$ind] . "_" . $ret . "." . $file->extension);
                    }
                    $dir = wa()->getConfig()->getRootUrl().'wa-data/public/shop/plugins/uniparams/img/uploaded/' . $data['lpkeyname'][$ind] . "_" . $ret . "." . $file->extension;

                    $list_params->insert(array('key_name' => $val, 'list_id' => $ret,
                        'type' => $data['lptype'][$ind], 'content' => $dir));
                } else {
                    $list_params->insert(array('key_name' => $val, 'list_id' => $ret,
                        'type' => $data['lptype'][$ind], 'content' => $data['lpparam'][$ind]));
                }
            }
        }
        $this->response['list_id'] = $ret;
    }

}