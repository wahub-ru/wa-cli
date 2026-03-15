<?php

/**
 * ACTION class shopApiextensionPluginFrontendReviewsVotesController
 *
 * @author Steemy, created by 25.08.2021
 */

class shopApiextensionPluginFrontendReviewsVoteController extends waJsonController
{
    public function execute()
    {
        // проверка что пользователь авторизован
        if(!wa()->getAuth()->isAuth()) {
            throw new waException('Not authorized', 403);
        }

        // Проверка CSRF если в настройках магазина активирована
        if (wa('shop')->getConfig()->getInfo('csrf') && waRequest::method() == 'post') {
            if (waRequest::post('_csrf') != waRequest::cookie('_csrf')) {
                throw new waException('CSRF Protection', 403);
            }
        }

        /**
         * apiextension_reviews_vote = array(
         *   'type'  => 'like' || 'dislike',
         *   'value' => 1 || 0
         * )
         */
        $voteParams  = waRequest::post('apiextension_reviews_vote', 0, waRequest::TYPE_ARRAY_TRIM);
        $reviewId  = waRequest::post('review_id', null, waRequest::TYPE_INT);

        if($reviewId && !empty($voteParams) && ($voteParams['type'] == 'like' || $voteParams['type'] == 'dislike')
            && ($voteParams['value'] == 1 || (int)$voteParams['value'] == 0))
        {
            $votes = array();
            $reviewsModel = new shopProductReviewsModel();
            $contactId = wa()->getUser()->getId();
            $apiextensionReviewsVoteModel = new shopApiextensionPluginReviewsVoteModel();

            $data = array(
                'vote_like'    => $voteParams['type'] == 'like' ? (int)$voteParams['value'] : 0,
                'vote_dislike' => $voteParams['type'] == 'dislike' ? (int)$voteParams['value'] : 0
            );

            // проверяем голосовал ли клиент
            $reviewVote = $apiextensionReviewsVoteModel->getByField(['review_id' => $reviewId, 'contact_id' => $contactId]);

            if($reviewVote) {
                $apiextensionReviewsVoteModel->updateByField(['review_id' => $reviewId, 'contact_id' => $contactId], $data);

                // исключаем дублирование
                if($reviewVote['vote_like'] != $data['vote_like'] || $reviewVote['vote_dislike'] != $data['vote_dislike']) {

                    // меняем общее количество голосования в основной таблице
                    $reviewVotesCount = $reviewsModel->getById($reviewId);
                    $votes = json_decode($reviewVotesCount['apiextension_votes'], true);

                    if(isset($votes['vote_like']) && isset($votes['vote_dislike'])) {
                        if ($reviewVote['vote_like'] != $data['vote_like']) {
                            $votes['vote_like'] = $data['vote_like'] == 0 ? $votes['vote_like'] - 1 : $votes['vote_like'] + 1;
                        }

                        if ($reviewVote['vote_dislike'] != $data['vote_dislike']) {
                            $votes['vote_dislike'] = $data['vote_dislike'] == 0 ? $votes['vote_dislike'] - 1 : $votes['vote_dislike'] + 1;
                        }
                    } else {
                        $votes['vote_like'] = $data['vote_like'];
                        $votes['vote_dislike'] = $data['vote_dislike'];
                    }

                    $reviewsModel->updateById($reviewId, ['apiextension_votes' => json_encode($votes, JSON_NUMERIC_CHECK)]);
                }

            } else {
                $data['review_id'] = $reviewId;
                $data['contact_id'] = $contactId;
                $apiextensionReviewsVoteModel->insert($data);

                // меняем общее количество голосования в основной таблице
                $reviewVotesCount = $reviewsModel->getById($reviewId);

                if($reviewVotesCount['apiextension_votes']) {
                    $votes = json_decode($reviewVotesCount['apiextension_votes'], true);
                    $votes['vote_like'] = $votes['vote_like'] + $data['vote_like'];
                    $votes['vote_dislike'] = $votes['vote_dislike'] + $data['vote_dislike'];

                } else {
                    $votes = array(
                        'vote_like' => $data['vote_like'],
                        'vote_dislike' => $data['vote_dislike']
                    );
                }

                $reviewsModel->updateById($reviewId, ['apiextension_votes' => json_encode($votes, JSON_NUMERIC_CHECK)]);
            }

            $this->response = array(
                'status' => true,
                'vote' => !empty($votes) ? $votes : $data
            );
        } else {
            $this->response = array(
                'status' => false,
                'error' => 'не указан review_id отзыва или неверно переданые type, value (должны быть "like || dislike" и 0 || 1)'
            );
        }
    }
}
