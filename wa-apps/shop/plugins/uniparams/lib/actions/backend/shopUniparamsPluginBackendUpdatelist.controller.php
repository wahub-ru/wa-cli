<?php

class shopUniparamsPluginBackendUpdatelistController extends waJsonController {
	
	public function execute() {
		$list = waRequest::post();
		$model = new shopUniparamsListsModel();

		$list = $list['li'];
		$db = $model->select('id, front_index')->order('front_index ASC')->fetchAll();
		$db_copy = $db;
        foreach ($db as $key => $val) {
		    $db_copy[$key]['front_index'] = array_search($db[$key]['front_index'], $list);
		}
		foreach ($db_copy as $key => $val) {
			$model->updateById($val['id'], array('front_index' => $val['front_index']));
		}

	}

}