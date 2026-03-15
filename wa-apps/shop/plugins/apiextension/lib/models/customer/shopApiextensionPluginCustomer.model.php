<?php

/**
 * Model class shopApiextensionPluginCustomerModel
 *
 * @author Steemy, created by 25.08.2021
 */

class shopApiextensionPluginCustomerModel extends waModel
{
    private $shop_customer = 'shop_customer';

    /**
     * Получить количество бонусов авторизованного пользователя
     * @param $contactId - идентификатор пользователя
     * @return bool|mixed
     * @throws waDbException
     */
    public function affiliateBonus($contactId)
    {
        $sql = "SELECT affiliate_bonus FROM `{$this->shop_customer}` WHERE contact_id=?";
        return $this->query($sql, (int)$contactId)->fetchField();
    }
}
