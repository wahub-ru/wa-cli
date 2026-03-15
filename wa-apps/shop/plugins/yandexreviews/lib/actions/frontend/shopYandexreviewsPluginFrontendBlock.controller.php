<?php

class shopYandexreviewsPluginFrontendBlockController extends waViewController
{
    public function execute()
    {
        /** @var shopYandexreviewsPlugin $plugin */
        $plugin = wa('shop')->getPlugin('yandexreviews');
        if (!$plugin) {
            $this->getResponse()->setStatus(404);
            echo 'Plugin not available';
            return;
        }

        $view  = waRequest::get('view',  'tiles', waRequest::TYPE_STRING_TRIM);
        $limit = max(1, (int) waRequest::get('limit', 8));
        $hide  = waRequest::get('hide', null);
        if ($hide === null) {
            $hide = !empty($plugin->getSettings()['hide_low_ratings']);
        } else {
            $hide = (bool)$hide;
        }

        $html = (new YandexReviewsRenderer($plugin))->render($view, $limit, $hide);

        $this->getResponse()->addHeader('X-Robots-Tag', 'noindex, nofollow');
        $this->setLayout(new waNullLayout());
        $this->view->assign('html', $html);
        $this->setTemplate('string:{$html}');
    }
}
