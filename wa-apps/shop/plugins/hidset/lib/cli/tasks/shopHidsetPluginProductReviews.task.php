<?php

class shopHidsetPluginProductReviewsTask extends shopHidsetPluginCli implements shopHidsetPluginCliTaskInterface
{
    public function run($params = null)
    {
        (new shopHidsetPluginRepair())->productReviewsAction();
    }

    public function getCommand(): string
    {
        return 'productReviews';
    }

    public function getDescription(): string
    {
        return <<<HTML
Восстанавливает структуру отзывов о товаре в случае если она нарушена
HTML;
    }
}