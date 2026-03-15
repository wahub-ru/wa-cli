<?php
/**
 * @author Serge Rodovnichenko <serge@syrnik.com>
 * @copyright Serge Rodovnichenko, 2021
 * @license Webasyst
 */

declare(strict_types=1);

/**
 * Class shopSyrattachPluginAttachmentsAction
 */
class shopSyrattachPluginAttachmentsAction extends waViewAction
{
    /** @var shopSyrattachPlugin */
    protected $plugin;

    /**
     * @throws waException
     */
    protected function preExecute()
    {
        parent::preExecute();
        $this->plugin = wa('shop')->getPlugin('syrattach');
    }

    /**
     * @throws waException
     */
    public function execute()
    {
        if (!($product_id = waRequest::get('id', null, waRequest::TYPE_INT)))
            throw new waException(_wp('Product ID required'));

        $product = new shopProduct($product_id);

        $attachments = (new shopSyrattachFileModel())->getByProductId($product_id);
        array_walk($attachments, function (&$attachment) {
            $attachment['url'] = shopSyrattachPlugin::getFileUrl($attachment);
        });

        $this->view->assign([
            'attachments'   => $attachments,
            'count'         => count($attachments),
            'max_file_size' => (int)waRequest::getUploadMaxFilesize(),
            'product'       => $product
        ]);
    }
}
