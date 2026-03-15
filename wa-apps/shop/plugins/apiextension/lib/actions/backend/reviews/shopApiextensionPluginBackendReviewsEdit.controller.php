<?php

/**
 * Edit fields review
 *
 * @author Steemy, created by 06.05.2018
 * @link http://steemy.ru/
 */

class shopApiextensionPluginBackendReviewsEditController extends waJsonController
{

    public function execute()
    {
        // Проверка CSRF если в настройках магазина активирована
        if (wa('shop')->getConfig()->getInfo('csrf') && waRequest::method() == 'post') {
            if (waRequest::post('_csrf') != waRequest::cookie('_csrf')) {
                throw new waException('CSRF Protection', 403);
            }
        }

        $pluginSetting = shopApiextensionPluginSettings::getInstance();
        $settings = $pluginSetting->getSettings();

        $body = '';
        $error = '';

        if ($settings['edit_fields_in_reviews']) {
            $reviewId  = htmlspecialchars(waRequest::post('apiextension_review_id', 0, waRequest::TYPE_INT));

            if($reviewId) {
                $productReviewsModel = new shopProductReviewsModel();
                if($productReviewsModel->getById($reviewId)) {
                    $message = htmlspecialchars(waRequest::post('apiextension_review', null, waRequest::TYPE_STRING_TRIM));
                    $experience = htmlspecialchars(waRequest::post('apiextension_experience', null, waRequest::TYPE_STRING_TRIM));
                    $dignity = htmlspecialchars(waRequest::post('apiextension_dignity', null, waRequest::TYPE_STRING_TRIM));
                    $limitations = htmlspecialchars(waRequest::post('apiextension_limitations', null, waRequest::TYPE_STRING_TRIM));
                    $recommend = htmlspecialchars(waRequest::post('apiextension_recommend', 0, waRequest::TYPE_INT));

                    $data = array(
                        'text' => $message,
                        'apiextension_experience' => $experience,
                        'apiextension_dignity' => $dignity,
                        'apiextension_limitations' => $limitations,
                        'apiextension_recommend' => $recommend,
                    );

                    try {

                        $productReviewsModel->updateById($reviewId, $data);
                        $body = 'поля успешно обновлены';

                    } catch (Exception $e) {
                        $error = 'не удалось обновить запись в бд';
                    }
                } else {
                    $error = 'не верно передан id отзыва';
                }
            } else {
                $error = 'не передан id отзыва';
            }
        } else {
            $error = 'запрещено настройках сайта';
        }

        $this->response = array(
            'body'   => $body,
            'error'  => $error,
        );
    }
}