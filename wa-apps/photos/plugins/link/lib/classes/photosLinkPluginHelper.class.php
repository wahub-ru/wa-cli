<?php
/**
 * Created by PhpStorm.
 * User: snark | itfrogs.ru
 * Date: 2/17/18
 * Time: 12:27 AM
 */

class photosLinkPluginHelper
{
    /**
     * @param $photo_id
     * @return array|null
     */
    public static function getLink($photo_id) {
        $link_model = new photosLinkPluginLinkModel();
        return $link_model->getById($photo_id);
    }

    /**
     * @param $photo_id
     * @return mixed
     */
    public static function getUrl($photo_id) {
        $link = self::getLink($photo_id);
        if (!empty($link)) {
            return $link['url'];
        }
        else return false;
    }
}