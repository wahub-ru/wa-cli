<?php

class shopUniparamsPluginBackendGetlistAction extends waViewAction {

    public function execute() {
        $model = new shopUniparamsListsModel();

        $list = $model->select('*')->order("front_index ASC")->fetchAll();
        $this->view->assign('lists', $list);
    }

}