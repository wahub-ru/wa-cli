<?php

class shopNbvarsPluginSettingsAction extends waViewAction
{
    /**
     * @var shopNbvarsVariableModel
     */
    protected $model;

    /**
     * shopNbvarsPluginSettingsSaveController constructor.
     * @throws waDbException
     * @throws waException
     */
    public function __construct()
    {
        parent::__construct();

        $this->model = new shopNbvarsVariableModel();
    }

    public function execute()
    {
        $plugin = wa('shop')->getPlugin('nbvars');

        $name = $plugin->getName();

        $variables = $this->model->getAll();

        $this->view->assign(compact('name'));
        $this->view->assign(compact('variables'));
    }
}
