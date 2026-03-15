<?php


class shopYandexreviewsPluginImportCli extends waCliController
{
    public function execute()
    {
        /** @var shopYandexreviewsPlugin $plugin */
        $plugin = wa('shop')->getPlugin('yandexreviews');
        if (!$plugin) {
            echo "Plugin not found\n";
            return;
        }

        $base_settings = $plugin->getSettings();
        $storefronts = shopYandexreviewsPlugin::getStorefrontOptions();
        if (!$storefronts) {
            $storefronts = ['' => 'all'];
        }

        $cm = new shopYandexreviewsCompanyModel();
        $importer = new YandexReviewsImporter();

        foreach ($storefronts as $storefront => $label) {
            $settings = shopYandexreviewsPlugin::applyStorefrontSettings($base_settings, $storefront);
            $storefront_label = $storefront !== '' ? $storefront : 'all';

            if (empty($settings['enabled'])) {
                echo "Disabled for storefront {$storefront_label}\n";
                continue;
            }

            echo "Enabled for storefront {$storefront_label}\n";

            $company_url = trim((string)($settings['company_url'] ?? ''));
            if ($company_url === '') {
                echo "Company URL is empty or invalid for storefront {$storefront_label}\n";
                continue;
            }

            $batch = (int)($settings['cron_batch_limit'] ?? 30);
            $batch = max(1, min(200, $batch));

            $yid = shopYandexreviewsPlugin::parseCompanyId($company_url);
            if (!$yid) {
                echo "Company URL invalid for storefront {$storefront_label}: {$company_url}\n";
                continue;
            }

            $company = $cm->getByYandexId($yid);
            if (!$company) {
                $now = date('Y-m-d H:i:s');
                $company = $cm->insert([
                    'yandex_company_id'   => (string)$yid,
                    'name'                => null,
                    'rating'              => null,
                    'reviews_total'       => null,
                    'url'                 => $company_url,
                    'last_fetch_datetime' => $now,
                    'create_datetime'     => $now,
                ]);
                $company = $cm->getById($company);
            }

            $res = $importer->runBatch($company, $yid, $batch, shopYandexreviewsPlugin::makeReviewsUrl($company_url));
            echo sprintf(
                "Done. Storefront: %s; Company: %s; Inserted: %d; Scanned: %d; Pages: %d\n",
                $storefront_label,
                $yid,
                $res['inserted'],
                $res['scanned'],
                $res['pages']
            );
        }
    }
}
