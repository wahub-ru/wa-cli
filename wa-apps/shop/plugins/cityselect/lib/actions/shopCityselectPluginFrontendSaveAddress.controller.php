<?php
/**
 * Сохранение адреса в корзине
 *
 * User: Echo-company
 * Email: info@echo-company.ru
 * Site: https://www.echo-company.ru
 */

class shopCityselectPluginFrontendSaveAddressController extends waJsonController
{
    public function execute()
    {

        $country = waRequest::post('country_iso_code', 'RU', waRequest::TYPE_STRING_TRIM);
        $country = shopCityselectHelper::getIso3fromIso2($country);

        //Не устанавливается индекс если он не запрашивается вместе с городом
        $city = waRequest::post('city', '', waRequest::TYPE_STRING_TRIM);
        $region = waRequest::post('region', '', waRequest::TYPE_STRING_TRIM);
        $zip = waRequest::post('zip', '', waRequest::TYPE_STRING_TRIM);


        $this->response = shopCityselectHelper::setCity($city, $region, $zip, $country);

    }
}