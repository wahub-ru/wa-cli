<?php

/**
 * Affiliate for review
 *
 * @author Steemy, created by 01.11.2021
 */

class shopApiextensionPluginReviewsAffiliate
{
    private $reviewsAffiliateModel;
    private $shopAffTrans;
    private $settings;

    public function __construct(){
        $this->reviewsAffiliateModel = new shopApiextensionPluginReviewsAffiliateModel();
        $this->shopAffTrans = new shopAffiliateTransactionModel();

        $pluginSetting = shopApiextensionPluginSettings::getInstance();
        $this->settings = $pluginSetting->getSettings();
    }

    /**
     * Получить товары за которые можно получить бонус за отзыв
     * @param $contactId - идентификатор пользователя
     * @return array
     * @throws waDbException
     * @throws waException
     */
    public function getProductsForReviewBonus($contactId)
    {
        if(!wa()->getUser()->isAuth() || !$this->settings['bonus_for_review_status']) return array();

        if(!$contactId) {
            $contactId = wa()->getUser()->getId();
        }

        return $this->reviewsAffiliateModel->getProductsForReviewBonus($contactId);
    }

    /**
     * Получить количество отзывов для товаров
     * @param $productId - информаци о товаре, массив
     */
    public function getBonusReviewForProduct($product)
    {
        if(!$this->settings['bonus_for_review_status'] || !$product) {
            return array();
        }

        return array(
            "apiextension_affiliate" => $this->getAffiliate($product, $product['sku_id']),
            "apiextension_affiliate_photo" => $this->getAffiliatePhoto($product, $product['sku_id'])
        );
    }

    /**
     * Начислить бонусы в момент написания отзыв
     * @param $params
     * @throws waDbException
     * @throws waException
     */
    public function addBonusesByWritingReview($params)
    {
        if($this->settings['bonus_for_review_status'] && $params['data']['parent_id'] == 0) {
            // update old
            $this->reviewsAffiliateModel->updateOld();

            $revAffiliate = $this->reviewsAffiliateModel->getByField(
                array(
                    'contact_id' => $params['data']['contact_id'],
                    'product_id' => $params['product']['id'],
                    'state' => shopApiextensionPluginReviewsAffiliateModel::STATE_AFFILIATE_ACTIVE,
                ));

            // проверяем есть ли заявка на начисление бонусов
            if(!empty($revAffiliate) && $revAffiliate['review_id'] == 0) {
                // если нет модерации то сразу начисляем бонусы
                if(!wa()->getSetting('moderation_reviews', 0)) {
                    // update bonuses
                    $this->updateBonuses($revAffiliate, $params['product'], $params['data']['images_count']);

                    // добавляем заявке id отзыва
                    $this->reviewsAffiliateModel->updateById(
                        $revAffiliate['id'],
                        array('review_id' => $params['id'])
                    );
                }
                // помечаем отзыв статусом на модерацию и добавляем id отзыва
                else {
                    // добавляем заявке id отзыва
                    $this->reviewsAffiliateModel->updateById(
                        $revAffiliate['id'],
                        array(
                            'review_id' => $params['id'],
                            'state' => shopApiextensionPluginReviewsAffiliateModel::STATE_AFFILIATE_MODERATION,
                        )
                    );
                }
            }
        }
    }

    /**
     * Изменение бонусов за отзывы при модерации в бекенде
     * @throws waDbException
     * @throws waException
     */
    public function addAffiliateWhenModerationBackend()
    {
        if (!$this->settings['bonus_for_review_status'])
            return;

        $reviewId = waRequest::post('review_id', null, waRequest::TYPE_INT);
        if (!$reviewId) {
            throw new waException("Unknown review id");
        }

        // update old
        $this->reviewsAffiliateModel->updateOld();

        $status = waRequest::post('status', '', waRequest::TYPE_STRING_TRIM);

        $shopProductReviewsModel = new shopProductReviewsModel();
        $review = $shopProductReviewsModel->getById($reviewId);

        $shopProductModel = new shopProductModel();
        $product = $shopProductModel->getById($review['product_id']);

        // если включена модерация отзывов в настройках магазина и статус равен approved
        // начисляем бонусы клиенту, если есть в записях начисления в статусе moderation
        if (wa()->getSetting('moderation_reviews', 0) && $status == shopProductReviewsModel::STATUS_PUBLISHED) {
            // проверяем что есть запись и это именно отзыв и статус публикация
            if(!empty($review) && $review['parent_id'] == 0
                && $review['status'] == shopProductReviewsModel::STATUS_PUBLISHED) {
                $revAffiliate = $this->reviewsAffiliateModel->getByField('review_id', $review['id']);
                // если есть заявка и статус на модерации, то добавляем бонусы и помечаем статусом completed
                if (!empty($revAffiliate) && $revAffiliate['state'] ==
                    shopApiextensionPluginReviewsAffiliateModel::STATE_AFFILIATE_MODERATION) {
                    $this->updateBonuses($revAffiliate, $product, $review['images_count']);
                }
            }
        }
        // если нажали удалить отзыв, то помечаем запись активной, если срок не вышел
        // или удаленной навсегда и восстановить будет не возможно
        else if ($status == shopProductReviewsModel::STATUS_DELETED) {
            $revAffiliate = $this->reviewsAffiliateModel->getByField('review_id', $review['id']);

            if (!empty($revAffiliate)) {
                // если начислялись бонусы уже за этот отзыва, то делаем возврат
                if ($revAffiliate['state'] == shopApiextensionPluginReviewsAffiliateModel::STATE_AFFILIATE_COMPLETED) {
                    $this->cancelBonuses($revAffiliate, $product['name']);
                }

                // если заявка еще активная, то делаем ее статус active
                if (strtotime($revAffiliate['create_datetime']) >=
                    strtotime('-'.$this->settings['bonus_for_review_days'].' day')) {
                    $this->reviewsAffiliateModel->updateById(
                        $revAffiliate['id'],
                        array(
                            'review_id' => 0,
                            'affiliate' => 0,
                            'state' => shopApiextensionPluginReviewsAffiliateModel::STATE_AFFILIATE_ACTIVE,
                        )
                    );
                } else {
                    // иначе помечаем статусом удалено
                    $this->reviewsAffiliateModel->updateById(
                        $revAffiliate['id'],
                        array('state' => shopApiextensionPluginReviewsAffiliateModel::STATE_AFFILIATE_DELETE));
                }

            }
        }
    }

    /**
     * При возврате заказа, списываем бонусы у клиента
     * @throws waException
     */
    public function cancelAffiliateWhenOrderRefund($params)
    {
        if($this->settings['bonus_for_review_status']) {
            // update old
            $this->reviewsAffiliateModel->updateOld();

            $shopOrderModel = new shopOrderModel();
            $order = $shopOrderModel->getById($params['order_id']);

            $shopOrderItemsModel = new shopOrderItemsModel();
            $orderItems = $shopOrderItemsModel->getItems($order['id']);

            // проверяем были ли начислены баллы и делаем отмену баллов
            if(!empty($orderItems)) {
                foreach ($orderItems as $item) {
                    $revAffiliates =
                        $this->reviewsAffiliateModel->getReviewsAffiliate(
                            $order['contact_id'],
                            $item['product_id'],
                            array(
                                shopApiextensionPluginReviewsAffiliateModel::STATE_AFFILIATE_COMPLETED,
                                shopApiextensionPluginReviewsAffiliateModel::STATE_AFFILIATE_ACTIVE,
                                shopApiextensionPluginReviewsAffiliateModel::STATE_AFFILIATE_MODERATION
                            )
                        );

                    // меняем статус на delete и делаем отмену баллов
                    if(!empty($revAffiliates)) {
                        foreach ($revAffiliates as $ra) {
                            // если есть выполненная запись о начисление бонусов, то отменяем их
                            if($ra['state'] == shopApiextensionPluginReviewsAffiliateModel::STATE_AFFILIATE_COMPLETED) {
                                $this->cancelBonuses($ra, $item['name']);
                            }

                            // обновляем статус у записи на - delete
                            $this->reviewsAffiliateModel->updateById(
                                $ra['id'],
                                array('state' => shopApiextensionPluginReviewsAffiliateModel::STATE_AFFILIATE_DELETE)
                            );
                        }
                    }
                }
            }

        }
    }

    /**
     * При переводе заказа в статус выполнено, делаем запись о возможности получить бонусы за отзыв
     * @throws waException
     */
    public function addAffiliateWhenOrderComplete($params)
    {
        if($this->settings['bonus_for_review_status']) {
            // update old
            $this->reviewsAffiliateModel->updateOld();

            $shopOrderModel = new shopOrderModel();
            $order = $shopOrderModel->getById($params['order_id']);

            $shopOrderItemsModel = new shopOrderItemsModel();
            $orderItems = $shopOrderItemsModel->getItems($order['id']);

            // получаем товары заказа и делаем записи в таблицу для начиселния бонусов, если еще не заносилась
            // если для товара создавалась запись и она в статусе активна, выполенена или на модерации,
            // то новой записи создано не будет
            if(!empty($orderItems)) {
                foreach($orderItems as $item) {
                    $revAffiliates =
                        $this->reviewsAffiliateModel->getReviewsAffiliate(
                            $order['contact_id'],
                            $item['product_id'],
                            array(
                                shopApiextensionPluginReviewsAffiliateModel::STATE_AFFILIATE_COMPLETED,
                                shopApiextensionPluginReviewsAffiliateModel::STATE_AFFILIATE_ACTIVE,
                                shopApiextensionPluginReviewsAffiliateModel::STATE_AFFILIATE_MODERATION
                            )
                        );

                    // если раньше записи не создавались уже для заказа
                    // добавляем запись для начисления бонусов за отзыв, бонус за товар будет расчитан уже в момент написания отзыва
                    if(empty($revAffiliates)) {
                        $this->reviewsAffiliateModel->insert(array(
                            'contact_id' => $order['contact_id'],
                            'order_id'   => $order['id'],
                            'product_id' => $item['product_id'],
                            'sku_id'     => $item['sku_id'],
                            'state'      => shopApiextensionPluginReviewsAffiliateModel::STATE_AFFILIATE_ACTIVE,
                        ));
                    }
                }
            }
        }
    }

    /**
     * Расчитать бонусы по правилам для отзыва
     * @param $product
     * @param $skuId
     * @return float|int
     * @throws waDbException
     * @throws waException
     */
    public function getAffiliate($product, $skuId)
    {
        $bonus = $this->settings['bonus_for_review_all'];
        $type = $this->settings['bonus_for_review_all_type'];
        $round = $this->settings['bonus_for_review_all_round'];

        //бонусы по категориям за отзыв
        if(!empty($this->settings['bonus_by_category'][$product['category_id']]['bonus'])) {
            $bonusByCategory = $this->settings['bonus_by_category'][$product['category_id']];
            $bonus = $bonusByCategory['bonus'];
            $type  = $bonusByCategory['type'];
            $round = $bonusByCategory['round'];
        }

        if($type == 'percent') {
            $bonus = $product['price'] * $bonus / 100;

        } else if($type == 'percent_purchase') {
            if($skuId) {
                $productSkus = new shopProductSkusModel();
                $skuId = $productSkus->getById($skuId);

                if (!empty($skuId['purchase_price']) && $skuId['purchase_price'] > 0 && $skuId['purchase_price'] < $product['price']) {
                    $bonus = ($product['price'] - $skuId['purchase_price']) * $bonus / 100;
                } else {
                    return 0;
                }
            } else {
                return 0;
            }
        }

        if($round == 'round_up') {
            $bonus = round($bonus, 0, PHP_ROUND_HALF_UP);
        } elseif ($round == 'round_down') {
            $bonus = round($bonus, 0, PHP_ROUND_HALF_DOWN);
        }

        // провекра на максимальный бонус
        if($bonus > $this->settings['bonus_max']) {
            $bonus = $this->settings['bonus_max'];
        }

        return $bonus;
    }

    /**
     * Расчитать бонусы по правилам для отзыва
     * @param $product
     * @param $skuId
     * @return float|int
     * @throws waDbException
     * @throws waException
     */
    public function getAffiliatePhoto($product, $skuId)
    {
        $bonus = $this->settings['bonus_for_review_all_photo'];
        $type = $this->settings['bonus_for_review_all_type'];
        $round = $this->settings['bonus_for_review_all_round'];

        //бонусы по категориям за отзыв с фото
        if(!empty($this->settings['bonus_by_category'][$product['category_id']]['bonus_photo'])) {
            $bonusByCategory = $this->settings['bonus_by_category'][$product['category_id']];
            $bonus = $bonusByCategory['bonus_photo'];
            $type  = $bonusByCategory['type'];
            $round = $bonusByCategory['round'];
        }

        if($type == 'percent') {
            $bonus = $product['price'] * $bonus / 100;

        } else if($type == 'percent_purchase') {
            if($skuId) {
                $productSkus = new shopProductSkusModel();
                $skuId = $productSkus->getById($skuId);

                if (!empty($skuId['purchase_price']) && $skuId['purchase_price'] > 0 && $skuId['purchase_price'] < $product['price']) {
                    $bonus = ($product['price'] - $skuId['purchase_price']) * $bonus / 100;
                } else {
                    return 0;
                }
            } else {
                return 0;
            }
        }

        if($round == 'round_up') {
            $bonus = round($bonus, 0, PHP_ROUND_HALF_UP);
        } elseif ($round == 'round_down') {
            $bonus = round($bonus, 0, PHP_ROUND_HALF_DOWN);
        }

        // провекра на максимальный бонус
        if($bonus > $this->settings['bonus_max_photo']) {
            $bonus = $this->settings['bonus_max_photo'];
        }

        return $bonus;
    }

    /**
     * Обновление бонусов
     * @param $revAffiliate
     * @param $product
     * @param $isPhoto
     * @throws waDbException
     * @throws waException
     */
    private function updateBonuses($revAffiliate, $product, $isPhoto) {
        if(!empty($revAffiliate)) {
            if($isPhoto) {
                $bonus = $this->getAffiliatePhoto($product, $revAffiliate['sku_id']);
            } else {
                $bonus = $this->getAffiliate($product, $revAffiliate['sku_id']);
            }

            if($bonus > 0) {
                $this->shopAffTrans->applyBonus(
                    $revAffiliate['contact_id'],
                    $bonus,
                    $revAffiliate['order_id'],
                    sprintf($this->settings['bonus_text'], $product['name']),
                    shopAffiliateTransactionModel::TYPE_ORDER_BONUS);

                // после начисления бонусов обновляем статус у записи на - completed
                $this->reviewsAffiliateModel->updateById(
                    $revAffiliate['id'],
                    array(
                        'affiliate' => $bonus,
                        'state' => shopApiextensionPluginReviewsAffiliateModel::STATE_AFFILIATE_COMPLETED,
                    )
                );
            }
        }
    }

    /**
     * Отмена бонусов
     * @param $revAffiliate
     * @param $productName
     */
    private function cancelBonuses($revAffiliate, $productName) {
        if(!empty($revAffiliate)) {
            $this->shopAffTrans->applyBonus(
                $revAffiliate['contact_id'],
                -$revAffiliate['affiliate'],
                $revAffiliate['order_id'],
                sprintf($this->settings['bonus_text_cancel'], $productName),
                shopAffiliateTransactionModel::TYPE_ORDER_CANCEL);
        }
    }
}
