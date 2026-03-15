<?php

class shopYandexreviewsPluginSettingsResetStorefrontsController extends waJsonController
{
    public function execute()
    {
        $model = new waAppSettingsModel();
        $table = $model->getTableName();
        $per_storefront_urls = [];
        $rows = $model->query(
            "SELECT value FROM {$table} WHERE app_id LIKE s:app AND name = 'company_url'",
            ['app' => 'shop.yandexreviews.%']
        )->fetchAll();
        foreach ($rows as $row) {
            $url = trim((string)($row['value'] ?? ''));
            if ($url !== '') {
                $per_storefront_urls[$url] = true;
            }
        }
        $global_url = trim((string)$model->get(['shop', 'yandexreviews'], 'company_url', ''));
        $model->exec("DELETE FROM {$table} WHERE app_id = s:app AND name LIKE 'yandexreviews.%'", [
            'app' => 'shop',
        ]);

        $storefronts = shopYandexreviewsPlugin::getStorefrontOptions();
        if ($storefronts) {
            foreach (array_keys($storefronts) as $storefront) {
                $model->del(shopYandexreviewsPlugin::getSettingsKeyForStorefront($storefront));
            }
        }

        if ($per_storefront_urls) {
            $used_yids = [];
            if ($global_url !== '') {
                $global_yid = shopYandexreviewsPlugin::parseCompanyId($global_url);
                if ($global_yid) {
                    $used_yids[$global_yid] = true;
                }
            }
            $cm = new shopYandexreviewsCompanyModel();
            foreach (array_keys($per_storefront_urls) as $url) {
                $yid = shopYandexreviewsPlugin::parseCompanyId($url);
                if ($yid && !isset($used_yids[$yid])) {
                    $cm->deleteByField('yandex_company_id', (string)$yid);
                } elseif (!$yid) {
                    $cm->deleteByField('url', $url);
                }
            }
        }

        $this->response = [
            'status' => 'ok',
        ];
    }
}
