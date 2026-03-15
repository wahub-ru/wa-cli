<?php /** @noinspection ALL */

class shopUniparamsPlugin extends shopPlugin
{
    const APP = 'shop';
    const PLUGIN_ID = 'uniparams';
    const CONFIG_FILE = 'config.php';

    function getList($list_key) {
        $lists = new shopUniparamsListsModel();
        $itemsModel = new shopUniparamsItemsModel();
        $itemsvModel = new shopUniparamsItemsValsModel();
        $listFields = new shopUniparamsListsFieldsModel();
        $list_params = new shopUniparamsListsParamsModel();

        $list = $lists->getByField(array("key_name" => $list_key));

        if (!$list || empty($list)) {
            return array("error" => "Нет списков с указанным именем");
        }

        $items = $itemsModel->query("SELECT * FROM shop_uniparams_items WHERE list_id=i:list_id AND enabled=1 ORDER BY front_index ASC", array('list_id' => $list['id']))->fetchAll();
        $params = $list_params->getByField(array('list_id' => $list['id']), true);

        $return = array();
        $return['id'] = htmlspecialchars($list['key_name']);
        $return['title'] = htmlspecialchars($list['name']);
        $return['description'] = htmlspecialchars($list['description']);
        if (!empty($params)) {
            foreach ($params as $param) {
                $return[$param['key_name']] = htmlspecialchars($param['content']);
            }
        }

        if (!empty($items)) {
            $return['items'] = array();
            foreach ($items as $key => $item) {
                $vals = array();
                $itemsv = $itemsvModel->getByField(array('item_id' => $item['id']), true);
                foreach ($itemsv as $key2 => $val) {
                    $field = $listFields->getById($val['field_id']);
                    $vals[$field['keyname']] = htmlspecialchars($val['content']);
                }
                $return['items'][] = $vals;
            }
        } else {
            $return['items'] = null;
        }

        return $return;
    }

}
