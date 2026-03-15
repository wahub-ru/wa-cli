<?php

class shopYandexreviewsCompanyModel extends waModel
{
    protected $table = 'shop_yandexreviews_company';

    public function getByYandexId($yid)
    {
        return $this->getByField('yandex_company_id', (string)$yid);
    }
}
