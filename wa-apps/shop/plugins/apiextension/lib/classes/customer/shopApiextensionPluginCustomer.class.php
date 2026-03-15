<?php

/**
 * Helper class shopApiextensionPluginCustomer
 *
 * @author Steemy, created by 25.08.2021
 */

class shopApiextensionPluginCustomer
{
    private $apiextensionCustomerModel;

    public function __construct(){
        $this->apiextensionCustomerModel = new shopApiextensionPluginCustomerModel();
    }

    /**
     * Получить количество бонусов авторизованного пользователя
     * @param $contactId - идентификатор пользователя
     * @return bool|mixed
     * @throws waDbException
     * @throws waException
     */
    public function affiliateBonus($contactId)
    {
        if(!wa()->getUser()->isAuth()) return '';

        if(!$contactId) {
            $contactId = wa()->getUser()->getId();
        }

        return $this->apiextensionCustomerModel->affiliateBonus($contactId);
    }
}