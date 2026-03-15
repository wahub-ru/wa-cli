<?php

/**
 * HELPER FOR REVIEWS
 *
 * @author Steemy, created by 17.08.2021
 */

class shopApiextensionPluginReviews
{
    private $apiextensionReviewsModel;
    private $settings;

    public function __construct(){
        $this->apiextensionReviewsModel = new shopApiextensionPluginReviewsModel();
        $pluginSetting = shopApiextensionPluginSettings::getInstance();
        $this->settings = $pluginSetting->getSettings();
    }

    /**
     * Получить количество отзывов для товаров
     * @param $productIds - список ид товаров
     * @return array|mixed
     * @throws waDbException
     * @throws waException
     */
    public function reviewsCount($productIds)
    {
        if(!$productIds) return array();

        if(is_array($productIds)) {
            $productIdsString = implode(',', $productIds);
        } else {
            $productIdsString = $productIds;
        }

        if ($cache = wa('shop')->getCache()) {
            $reviewsCount = $cache->get('apiextension_product_reviews_count_' . $productIdsString);
            if ($reviewsCount !== null) {
                return $reviewsCount;
            }
        }

        if(!is_array($productIds)) {
            $productIds = explode(',', $productIds);
        }

        $reviewsCount = $this->apiextensionReviewsModel->reviewsCount($productIds);

        if (!empty($cache) && $productIdsString) {
            $cache->set('apiextension_product_reviews_count_' . $productIdsString, $reviewsCount, 7200);
        }

        return $reviewsCount;
    }

    /**
     * Добавляем поля только для отзыва и если разрешено в настройках плагина
     * @param $params
     */
    public function addAdditionalFields($params)
    {
        if ($this->settings['additional_fields_review'] && $params['data']['parent_id'] == 0) {
            $params['data']['apiextension_experience'] =
                htmlspecialchars(waRequest::post('apiextension_experience', null, waRequest::TYPE_STRING_TRIM));

            $params['data']['apiextension_dignity'] =
                htmlspecialchars(waRequest::post('apiextension_dignity', null, waRequest::TYPE_STRING_TRIM));

            $params['data']['apiextension_limitations'] =
                htmlspecialchars(waRequest::post('apiextension_limitations', null, waRequest::TYPE_STRING_TRIM));

            $params['data']['apiextension_recommend'] =
                htmlspecialchars(waRequest::post('apiextension_recommend', 0, waRequest::TYPE_INT));
        }
    }

    /**
     * Показываем дополнительные поля для отзывов в админке
     * @param $params
     * @return string
     * @throws waException
     */
    public function showAdditionalFieldsReviewBackend($params)
    {
        if(!$this->settings['additional_fields_review'] || !$params['reviews']) {
            return '';
        }

        $additionalFields = array();
        foreach ($params['reviews'] as $r) {
            $additionalFields[$r['id']]['apiextension_recommend'] = $r['apiextension_recommend'] ? $r['apiextension_recommend'] : 0;
            $additionalFields[$r['id']]['apiextension_experience'] = $r['apiextension_experience'] ? $r['apiextension_experience'] : "";
            $additionalFields[$r['id']]['apiextension_dignity'] = $r['apiextension_dignity'] ? $r['apiextension_dignity'] : "";
            $additionalFields[$r['id']]['apiextension_limitations'] = $r['apiextension_limitations'] ? $r['apiextension_limitations'] : "";
            $additionalFields[$r['id']]['apiextension_votes'] = json_decode($r['apiextension_votes'], true);
        }

        $script = '';
        if ($additionalFields) {
            $options = json_encode(array(
                "additionalFields" => $additionalFields,
                "delete" => $this->settings['delete_reviews'],
                "ui_version" => wa()->whichUI(),
            ));

            $version = $this->settings['plugin_info']['version'];
            $urlPluginCSS = wa()->getRootUrl() . "wa-apps/shop/plugins/apiextension/css/backend.reviews.css?v=" . $version;
            $urlPluginJS = wa()->getRootUrl() . "wa-apps/shop/plugins/apiextension/js/backend.reviews.js?v=" . $version;

            $script = "
<link href=\"{$urlPluginCSS}\" rel=\"stylesheet\"></link>
<script src=\"{$urlPluginJS}\"></script>
<script>
    $(function() {
        $.backendReviews.init({$options});
    });
</script>";
        }

        return $script;
    }

    /**
     * Получить голосование клиента по отзывам
     * @param $reviewIds - список ид отзывов
     * @param $contactId - идентификатор пользователя
     * @return array
     * @throws waDbException
     * @throws waException
     */
    public function getReviewsVote($reviewIds, $contactId)
    {
        if(!$reviewIds || !wa()->getAuth()->isAuth()) return array();

        if(is_array($reviewIds)) {
            $reviewIds = implode(',', $reviewIds);
        }

        if(!$contactId) {
            $contactId = wa()->getUser()->getId();
        }

        $apiextensionReviewsVoteModel = new shopApiextensionPluginReviewsVoteModel();

        return
            $apiextensionReviewsVoteModel
                ->select('*')
                ->where("contact_id={$contactId} and review_id IN({$reviewIds})")
                ->fetchAll('review_id');
    }

    /**
     * Удалить отзыв и фото для отзыва
     * @throws Exception
     * @throws waDbException
     * @throws waException
     */
    public function removeReview() {
        if (!$this->settings['delete_reviews'])
            return;

        $reviewId = waRequest::post('review_id', null, waRequest::TYPE_INT);
        if (!$reviewId) {
            throw new waException("Unknown review id");
        }

        $status = waRequest::post('status', '', waRequest::TYPE_STRING_TRIM);

        if ($status == shopProductReviewsModel::STATUS_DELETED) {
            // удаялем отзыв и делаем ремонт
            $reviewsModel = new shopProductReviewsModel();
            $reviewsModel->deleteById($reviewId);
            $reviewsModel->repair();

            // удаляем картинки для отзыва
            $reviewImagesModel = new shopProductReviewsImagesModel();
            $reviewsImages = $reviewImagesModel->getByField('review_id', $reviewId, true);
            foreach ($reviewsImages as $images) {
                $reviewImagesModel->remove($images['id']);
            }
        }
    }
}
