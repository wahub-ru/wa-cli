<?php
/**
 * File Upload controller
 *
 * @package Syrattach/controller
 * @author Serge Rodovnichenko <serge@syrnik.com>
 * @copyright (c) 2014-2021, Serge Rodovnichenko
 * @license http://www.webasyst.com/terms/#eula Webasyst
 */

declare(strict_types=1);

/**
 * Class shopSyrattachPluginAttachmentsUploadController
 */
class shopSyrattachPluginAttachmentsUploadController extends shopUploadController
{
    /** @var shopProductModel */
    private $Product;

    /** @var shopSyrattachFileModel */
    private $SyrattachFile;


    public function __construct()
    {
        $this->Product = new shopProductModel();
        $this->SyrattachFile = new shopSyrattachFileModel();
    }

    /**
     *
     * @param waRequestFile $file
     * @return array
     * @throws waException
     */
    protected function save(waRequestFile $file): array
    {
        $product_id = waRequest::post('syrattach_product_id', null, waRequest::TYPE_INT);
        if (!$product_id) throw new waException("Не указан идентификатор товара");
        $this->checkProductRights($product_id);
        return $this->SyrattachFile->add($product_id, $file);
    }

    /**
     * Throws an error if user hasn't enough rights to access product
     * We're trying to keep our main method clean
     *
     * @param int $product_id
     * @throws waException
     */
    private function checkProductRights(int $product_id)
    {
        if (!$this->Product->checkRights($product_id)) {
            throw new waException(_wp('Access denied'));
        }
    }
}
