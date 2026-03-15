<?php

/**
 * @author Serge Rodovnichenko <serge@syrnik.com>
 * @version
 * @copyright Serge Rodovnichenko, 2017
 * @license
 */
class shopTipsPluginProductLogAction extends waViewAction
{
    /** @var shopTipsLogModel */
    protected $Log;

    public function execute()
    {
        $actions = $errors = array();

        $product_id = (int)$this->getRequest()->get('product_id', 0, waRequest::TYPE_INT);
        $pagination = array('total' => 0, 'total_pages' => 1, 'page' => 1, 'start' => 0, 'limit' => 0);

        if (!$product_id) {
            $errors[] = _wp('Unknown Product ID');
        } else {
            $pagination = $this->paginate($product_id);
            $actions = array_values($this->Log->getProductLog($product_id, array($pagination['start'], $pagination['limit'])));
        }

        $this->view->assign(compact('actions', 'errors', 'pagination', 'product_id'));
    }


    protected function preExecute()
    {
        parent::preExecute();
        $this->Log = new shopTipsLogModel();
    }

    protected function paginate($product_id)
    {
        $limit = 25;

        $page = (int)$this->getRequest()->get('page', 1, waRequest::TYPE_INT);
        if ($page < 1) {
            $page = 1;
        }

        $total = (int)$this->Log->countByProduct($product_id);

        $total_pages = (int)ceil($total / $limit);
        if ($total_pages < 1) {
            $total_pages = 1;
        }

        if ($page > $total_pages) {
            $page = $total_pages;
        }

        return array(
            'total'       => $total,
            'total_pages' => $total_pages,
            'page'        => $page,
            'start'       => ($page - 1) * $limit,
            'limit'       => $limit
        );
    }
}
