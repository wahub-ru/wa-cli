<?php

/**
 * Запуск:
 *  php wa-system/cli.php shop yandexreviewsPluginAvatars [limit=100]
 */
class shopYandexreviewsPluginAvatarsCli extends waCliController
{
    public function execute()
    {
        $limit = (int)($this->params['limit'] ?? 100);
        if ($limit < 1) $limit = 100;

        $plugin = wa('shop')->getPlugin('yandexreviews');
        if (!$plugin) { echo "Plugin not found\n"; return; }

        $s = $plugin->getSettings();
        $company_urls = shopYandexreviewsPlugin::getCompanyUrlsForImport($s);
        if (!$company_urls) { echo "Company URL invalid\n"; return; }

        $cm = new shopYandexreviewsCompanyModel();
        $rm = new shopYandexreviewsReviewModel();
        $svc = new YandexReviewsAvatarService();

        foreach ($company_urls as $url) {
            $yid = shopYandexreviewsPlugin::parseCompanyId($url);
            if (!$yid) { echo "Company URL invalid: {$url}\n"; continue; }

            $company = $cm->getByYandexId($yid);
            if (!$company) { echo "Company not found (yid={$yid})\n"; continue; }

            $rows = $rm->select('*')
                ->where('company_id = i:cid AND author_avatar IS NOT NULL AND author_avatar != \"\" AND (author_avatar_local IS NULL OR author_avatar_local = \"\")', ['cid'=>(int)$company['id']])
                ->limit($limit)->fetchAll();

            if (!$rows) { echo "Nothing to fetch for company {$yid}\n"; continue; }

            $done = 0; $fail = 0;
            foreach ($rows as $r) {
                $fname = $svc->downloadAndStore((int)$company['id'], $r['author_avatar']);
                if ($fname) {
                    $rm->updateById($r['id'], ['author_avatar_local' => $fname]);
                    $done++;
                } else {
                    $fail++;
                }
            }

            echo "Done: company={$yid} ok={$done}, fail={$fail}\n";
        }
    }
}
