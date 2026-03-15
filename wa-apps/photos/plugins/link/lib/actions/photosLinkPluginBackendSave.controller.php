<?php
/**
 * Created by PhpStorm.
 * User: snark | itfrogs.ru
 * Date: 2/17/18
 * Time: 12:04 AM
 */

class photosLinkPluginBackendSaveController extends waJsonController
{
    /**
     * @throws waException
     */
    public function execute()
    {
        $data = waRequest::post();
        $link_model = new photosLinkPluginLinkModel();
        if (isset($data['url']) && !empty($data['url'])) {
            $link_model->insert($data, 1);
        }

        if (isset($data['url']) && isset($data['photo_id']) && is_numeric($data['photo_id']) && empty($data['url'])) {
            $link_model->deleteById($data['photo_id']);
        }

    }
}