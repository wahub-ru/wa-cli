<?php

class shopYandexreviewsPluginSettingsDeleteReviewsController extends waJsonController
{
    public function execute()
    {
        if (waRequest::method() !== 'post') {
            $this->response = [
                'status' => 'fail',
                'message' => 'Invalid request',
            ];
            return;
        }

        $rm = new shopYandexreviewsReviewModel();
        $storefront = waRequest::post('storefront', '', waRequest::TYPE_STRING_TRIM);
        $storefront = shopYandexreviewsPlugin::normalizeStorefront($storefront);

        $storefronts = shopYandexreviewsPlugin::getStorefrontOptions();
        if ($storefront !== '' && !array_key_exists($storefront, $storefronts)) {
            $this->response = [
                'status' => 'fail',
                'message' => 'Витрина не найдена.',
            ];
            return;
        }

        if ($storefront === '') {
            $rm->exec('DELETE FROM shop_yandexreviews_review');
            $cm = new shopYandexreviewsCompanyModel();
            $cm->exec('DELETE FROM shop_yandexreviews_company');
            $this->removeDataDir('plugins/yandexreviews/photos/');
            $this->removeDataDir('plugins/yandexreviews/avatars/');

            $this->response = [
                'status' => 'ok',
            ];
            return;
        }

        /** @var shopYandexreviewsPlugin $plugin */
        $plugin = wa('shop')->getPlugin('yandexreviews');
        if (!$plugin) {
            $this->response = [
                'status' => 'fail',
                'message' => 'Плагин не найден.',
            ];
            return;
        }

        $settings = $plugin->getSettings();
        $company_url = shopYandexreviewsPlugin::getCompanyUrlForStorefront($settings, $storefront);
        if ($company_url === '') {
            $this->response = [
                'status' => 'fail',
                'message' => 'Не найдена компания для витрины.',
            ];
            return;
        }

        $yid = shopYandexreviewsPlugin::parseCompanyId($company_url);
        if (!$yid) {
            $this->response = [
                'status' => 'fail',
                'message' => 'Не найдена компания для витрины.',
            ];
            return;
        }

        $cm = new shopYandexreviewsCompanyModel();
        $company = $cm->getByYandexId($yid);
        if (!$company) {
            $this->response = [
                'status' => 'fail',
                'message' => 'Не найдена компания для витрины.',
            ];
            return;
        }

        $company_id = (int)$company['id'];
        $rm->deleteByField('company_id', $company_id);
        $cm->deleteById($company_id);
        $this->removeDataDir('plugins/yandexreviews/photos/' . $company_id . '/');
        $this->removeDataDir('plugins/yandexreviews/avatars/' . $company_id . '/');

        $this->response = [
            'status' => 'ok',
        ];
    }

    private function removeDataDir(string $rel_dir): void
    {
        $abs = wa()->getDataPath($rel_dir, false, 'shop');
        if (!$abs || !is_dir($abs)) {
            return;
        }

        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($abs, FilesystemIterator::SKIP_DOTS),
            RecursiveIteratorIterator::CHILD_FIRST
        );

        foreach ($iterator as $item) {
            if ($item->isDir()) {
                @rmdir($item->getPathname());
            } else {
                @unlink($item->getPathname());
            }
        }

        @rmdir($abs);
    }
}
