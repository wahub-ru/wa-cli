<?php
/**
 * @author Serge Rodovnichenko <serge@syrnik.com>
 * @copyright Serge Rodovnichenko, 2021
 * @license Webasyst
 */
declare(strict_types=1);

/**
 * Class shopSyrattachPluginBackendAttachmentsAction
 */
class shopSyrattachPluginBackendAttachmentsAction extends waViewAction
{
    /**
     * @throws waException
     */
    public function execute()
    {
        $id = waRequest::param('id', 0, waRequest::TYPE_INT);
        $product = new shopProduct($id);

        $this->view->assign('product', $product);
        $this->view->assign('plugin_version', wa('shop')->getPlugin('syrattach')->getVersion());
        $this->view->assign('attachments', $id ? array_values((new shopSyrattachFileModel())->getByProductId($id, true)) : []);
        $this->view->assign('max_upload_size', (int)waRequest::getUploadMaxFilesize());

        $this->setLayout(new shopBackendProductsEditSectionLayout([
            'product'    => $product,
            'content_id' => 'attachments'
        ]));
    }

    /**
     * @return string
     */
    protected function getTemplate(): string
    {
        return $this->getPluginRoot() . 'templates/actions/attachments/index.html';
    }
}
