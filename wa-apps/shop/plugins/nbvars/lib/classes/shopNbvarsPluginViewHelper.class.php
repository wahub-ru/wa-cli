<?php

class shopNbvarsPluginViewHelper extends waPluginViewHelper
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

    /**
     * @param $var
     * @return string
     * @throws waException
     */
    public function v($var)
    {
        if(!$var)
            return null;

        $cache = new waSerializeCache('shop_nbvars_' . $var, 3600, 'shop');

        if($cache->get() && $cache->isCached())
            return $cache->get();

        $data = $this->model->getByField('name', $var);
        if($data['value'])
            $cache->set($data['value']);

        return $data['value'];
    }

}
