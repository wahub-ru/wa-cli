<?php

class shopUniparamsPluginBackendUpdateitemsposController extends waJsonController {
	
	public function execute() {
		$list = waRequest::post();
		$model = new shopUniparamsItemsModel();

        $list_id = $list['list_id'];
		$list = $list['li'];
		$db = $model->query("SELECT id, front_index FROM shop_uniparams_items WHERE list_id=i:list_id ORDER BY front_index ASC", array('list_id' => $list_id))->fetchAll();
		$db_copy = $db;
        foreach ($db as $key => $val) {
		    $db_copy[$key]['front_index'] = array_search($key, $list);
		}
		foreach ($db_copy as $key => $val) {
			$model->updateById($val['id'], array('front_index' => $val['front_index']));
		}

	}

}