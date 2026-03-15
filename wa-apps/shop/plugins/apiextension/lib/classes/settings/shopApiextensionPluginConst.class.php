<?php

/**
 * CONSTANS
 *
 * @author Steemy, created by 21.07.2021
 */

class shopApiextensionPluginConst
{
    /**
     * Название плагина
     * @return string
     */
    public function getNamePlugin()
    {
        return 'apiextension';
    }

    /**
     * Возвращает массив настроек по умолчанию
     * @return array
     * @throws waException
     */

    public function getSettingsDefault()
    {
        return array(
            'additional_fields_review'   => 0,
            'edit_fields_in_reviews'     => 0,
            'delete_reviews'             => 0,
            'bonus_for_review_status'    => 0,
            'bonus_for_review_all'       => 0,
            'bonus_for_review_all_photo' => 0,
            'bonus_for_review_all_type'  => 'number',
            'bonus_for_review_all_round' => 'round_no',
            'bonus_for_review_days'      => 30,
            'bonus_text'                 => 'Бонусы за отзыв о товаре - %s',
            'bonus_text_cancel'          => 'Отмена бонусов за отзыв о товаре - %s',
            'bonus_by_category'          => array(),
            'bonus_max'                  => 1000,
            'bonus_max_photo'            => 1500,
            'additional_links'           => 0,
            'plugin_info'                => wa()->getConfig()->getAppConfig('shop')->getPluginInfo($this->getNamePlugin()),
        );
    }
}