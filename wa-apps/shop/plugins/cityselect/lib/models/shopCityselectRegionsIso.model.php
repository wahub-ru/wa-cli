<?php

/**
 * User: Echo-company
 * Email: info@echo-company.ru
 * Site: https://www.echo-company.ru
 */
class shopCityselectRegionsIsoModel extends waModel
{
    public $table = "shop_cityselect__regions_iso";

    protected static $instance;

    public static function updateRegionsIso($country, $regions)
    {
        $model = self::getInstance();

        $model->deleteByField(array('country_iso3' => $country));

        return $model->multipleInsert($regions);
    }

    public static function getByCountry($country)
    {
        $model = self::getInstance();
        return $model->getByField('country_iso3', $country, true);
    }

    /**
     * @return shopCityselectRegionsIsoModel
     */
    public static function getInstance()
    {
        if (!self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public static function findByIsoCode($country, $iso, $short_iso = '')
    {
        $model = self::getInstance();

        $country = $model->escape($country);
        $iso = $model->escape($iso);
        $short_iso = empty($short_iso) ? $iso : $model->escape($short_iso);

        $find = $model->where("country_iso3 = '" . $country . "'")
            ->where("(region_iso = '$iso' or region_iso = '$short_iso')")
            ->fetch();

        return $find;

    }
}