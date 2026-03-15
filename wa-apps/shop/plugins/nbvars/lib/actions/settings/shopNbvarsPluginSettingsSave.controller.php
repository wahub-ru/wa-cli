<?php

class shopNbvarsPluginSettingsSaveController extends waController
{
    /**
     * @var shopNbvarsVariableModel
     */
    protected $model;

    /**
     * @var string
     */
    protected $url;

    /**
     * shopNbvarsPluginSettingsSaveController constructor.
     * @throws waDbException
     * @throws waException
     */
    public function __construct()
    {
        $this->model = new shopNbvarsVariableModel();
        $this->url = wa()->getAppUrl();
    }

    public function execute()
    {
        $name = waRequest::post('ids');
        $value = waRequest::post('val');

        $this->model->query("truncate " . $this->model->getTableName());

        if(
            is_array($name) &&
            is_array($value) &&
            !empty($name) &&
            !empty($value)
        ){
            $variables = array_combine($name, $value);

            $data = [];
            foreach ($variables as $name => $value)
                $data[] = ['name' => $name, 'value' => $value];

            $this->model->multipleInsert($data);
        }

        $this->redirect($this->url . '?action=plugins#/nbvars');
    }
}
