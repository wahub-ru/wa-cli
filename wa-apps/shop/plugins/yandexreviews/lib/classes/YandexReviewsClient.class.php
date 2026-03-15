<?php

class YandexReviewsClient
{
    private const DIGEST_URL = 'https://yandex.ru/ugcpub/digest';

    private $ua_pool = [
        'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:129.0) Gecko/20100101 Firefox/129.0',
        'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/127.0.0.0 Safari/537.36',
        'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/127.0.0.0 Safari/537.36',
    ];

    private $cookie_file;
    private $photo_debug_logged = false;

    public function __construct()
    {
        $this->cookie_file = wa()->getTempPath('yandexreviews_cookies.txt', 'shop');
        if (!file_exists($this->cookie_file)) {
            @touch($this->cookie_file);
        }
    }

    public function fetch($yandex_company_id, $limit = 20, $referer_url = null, $offset = 0)
    {
        $this->photo_debug_logged = false;

        $limit  = max(1, min(50, (int)$limit));
        $offset = max(0, (int)$offset);

        if (!$referer_url) {
            $referer_url = sprintf(
                'https://yandex.ru/maps/org/%s/%s/reviews/?tab=reviews',
                rawurlencode('koinsmos'),
                rawurlencode($yandex_company_id)
            );
        }

        $variants = [
            ['tag' => 'SPRAV/Org', 'objectId' => '/sprav/'.$yandex_company_id, 'otype' => 'Org', 'appId' => '1org-viewer'],
            ['tag' => 'ORG/Org',   'objectId' => '/org/'.$yandex_company_id,   'otype' => 'Org', 'appId' => '1org-viewer'],
            ['tag' => 'SPRAV/Biz', 'objectId' => '/sprav/'.$yandex_company_id, 'otype' => 'Biz', 'appId' => '1org-viewer'],
            ['tag' => 'ORG/Biz',   'objectId' => '/org/'.$yandex_company_id,   'otype' => 'Biz', 'appId' => '1org-viewer'],
        ];

        foreach ($variants as $v) {
            $params = [
                'offset'      => $offset,
                'objectId'    => $v['objectId'],
                'addComments' => 'true',
                'otype'       => $v['otype'],
                'appId'       => $v['appId'],
                'limit'       => $limit,
            ];
            $url = self::DIGEST_URL.'?'.http_build_query($params);

            $res = $this->httpGetJson($url, $referer_url, $v['tag']);
            if ($res === null) {
                continue;
            }

            $reviews = $this->extractReviews($res);
            if ($reviews) {
                return $reviews;
            }
        }

        waLog::log("YandexReviewsClient: both attempts returned empty/invalid payload for company={$yandex_company_id}", 'yandexreviews.log');
        return [];
    }

    /** ===== HTTP & логирование ===== */

    private function httpGetJson(string $url, string $referer, string $tag)
    {
        $ua = $this->ua_pool[array_rand($this->ua_pool)];

        $resp_headers = '';
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_TIMEOUT        => 30,
            CURLOPT_CONNECTTIMEOUT => 10,
            CURLOPT_HTTP_VERSION   => CURL_HTTP_VERSION_1_1,
            CURLOPT_IPRESOLVE      => CURL_IPRESOLVE_V4,
            CURLOPT_ENCODING       => 'gzip',
            CURLOPT_COOKIEFILE     => $this->cookie_file,
            CURLOPT_COOKIEJAR      => $this->cookie_file,
            CURLOPT_HTTPHEADER     => [
                'Accept: application/json, text/plain, */*',
                'Accept-Language: ru,en;q=0.9',
                'X-Requested-With: XMLHttpRequest',
                'Cache-Control: no-cache',
                'Pragma: no-cache',
                'DNT: 1',
                'Sec-Fetch-Mode: cors',
                'Sec-Fetch-Site: same-origin',
                'Expect:' // off 100-continue
            ],
            CURLOPT_USERAGENT      => $ua,
            CURLOPT_HEADERFUNCTION => function($ch, $header) use (&$resp_headers) {
                $resp_headers .= $header;
                return strlen($header);
            },
        ]);
        curl_setopt($ch, CURLOPT_REFERER, $referer);

        $body = curl_exec($ch);
        $err  = curl_error($ch);
        $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $ctype= curl_getinfo($ch, CURLINFO_CONTENT_TYPE);
        $eff  = curl_getinfo($ch, CURLINFO_EFFECTIVE_URL);
        curl_close($ch);

        if ($err || $code >= 400 || $body === false || $body === '') {
            waLog::log("YandexReviewsClient {$tag} HTTP error: code={$code}, err={$err}, ctype={$ctype}, url={$eff}", 'yandexreviews.log');
            $this->dumpRaw('HTTPERR '.$tag, $resp_headers, $body);
            return null;
        }

        if (stripos((string)$ctype, 'application/json') === false) {
            $this->dumpRaw('NONJSON '.$tag, $resp_headers, $body);
            waLog::log("YandexReviewsClient {$tag} non-JSON content-type: {$ctype}", 'yandexreviews.log');
            return null;
        }

        $json = json_decode($body, true);
        if (!is_array($json)) {
            waLog::log("YandexReviewsClient {$tag} JSON decode error; ctype={$ctype}", 'yandexreviews.log');
            $this->dumpRaw('JSONERR '.$tag, $resp_headers, $body);
            return null;
        }

        return $json;
    }

    private function dumpRaw(string $tag, string $headers, $body): void
    {
        $h = trim($headers);
        $b = is_string($body) ? substr($body, 0, 4000) : '';
        $payload = $h."\n\n".$b;
        // оставляем только для диагностики не-JSON/HTTP ошибок
        waLog::log("[$tag] ".date('c')."\n".$payload."\n", 'yandexreviews.last.html');
    }

    /** Обрезаем ответ для лога (если понадобится) */
    private function shorten(array $j): array
    {
        $limit = function($arr){ return is_array($arr) ? array_slice($arr, 0, 5, true) : $arr; };

        if (isset($j['entities'])) {
            foreach (['reviews','users','authors'] as $k) {
                if (isset($j['entities'][$k])) {
                    $j['entities'][$k] = $limit($j['entities'][$k]);
                }
            }
        }
        if (isset($j['view']['views'])) {
            $j['view']['views'] = $limit($j['view']['views']);
        }
        if (isset($j['views'])) {
            $j['views'] = $limit($j['views']);
        }
        return $j;
    }

    private function dumpLast(string $tag, array $payload): void
    {
        // отключено по требованию: JSON-дамп не пишем
        return;
    }

    /** ===== Нормализация ===== */

    private function normalizeRatingValue($rate)
    {
        if ($rate === null) return null;

        if (is_numeric($rate)) {
            $n = (int) round($rate);
            return max(1, min(5, $n));
        }
        if (is_string($rate) && is_numeric($rate)) {
            $n = (int) round((float)$rate);
            return max(1, min(5, $n));
        }
        if (is_array($rate)) {
            foreach (['value','score','general','rating','grade'] as $k) {
                if (array_key_exists($k, $rate)) {
                    $n = $this->normalizeRatingValue($rate[$k]);
                    if ($n !== null) return $n;
                }
            }
            foreach ($rate as $v) {
                $n = $this->normalizeRatingValue($v);
                if ($n !== null) return $n;
            }
        }
        return null;
    }

    private function normalizeDateValue($dt)
    {
        if ($dt === null || $dt === '') return null;
        if (is_numeric($dt)) {
            $n = (int)$dt;
            if ($n > 9999999999) $n = (int) round($n / 1000);
            if ($n > 0) return date('Y-m-d H:i:s', $n);
        }
        $ts = strtotime((string)$dt);
        return $ts ? date('Y-m-d H:i:s', $ts) : null;
    }

    /** ===== Avatar utils ===== */

    /** годится ли URL-аватарки (без enc-/обрезков) */
    private function isGoodAvatarUrl(string $url): bool
    {
        if (strpos($url, '/enc-') !== false) return false;
        // если это get-yapic, убеждаемся что правая часть непустая и не оканчивается на "-"
        if (preg_match('~get-yapic/(\d{1,6})/([A-Za-z0-9_-]{1,64})~', $url, $m)) {
            $right = $m[2];
            if ($right === '' || stripos($right, 'enc-') === 0 || substr($right, -1) === '-') {
                return false;
            }
        }
        return true;
    }

    private function extractAvatarFromUser($u): ?string
{
    if (!is_array($u)) return null;

    $mk = function($id){
        $id = $this->normalizeYapicId((string)$id);           // отсекает enc- и обрезки
        return $id ? 'https://avatars.mds.yandex.net/get-yapic/'.$id.'/islands-68' : null;
    };

    // 1) прямые URL-строки
    foreach (['avatar','avatarUrl','photo','picture','image','img','icon','userIcon','userpic'] as $k) {
        if (isset($u[$k]) && is_string($u[$k]) && stripos($u[$k], 'http') === 0) {
            if ($this->isGoodAvatarUrl($u[$k])) return $u[$k];
        }
        if (isset($u[$k]) && is_array($u[$k])) {
            foreach (['url','orig','value','original'] as $kk) {
                if (!empty($u[$k][$kk]) && is_string($u[$k][$kk]) && stripos($u[$k][$kk], 'http') === 0) {
                    if ($this->isGoodAvatarUrl($u[$k][$kk])) return $u[$k][$kk];
                }
            }
            // 1b) внутри объекта могут лежать id
            foreach (['id','avatarId','yapicId','pic'] as $kk) {
                if (!empty($u[$k][$kk]) && is_string($u[$k][$kk])) {
                    if ($url = $mk($u[$k][$kk])) return $url;
                }
            }
        }
    }

    // 2) id на верхнем уровне
    foreach (['avatarId','yapicId','yapic','avatar_id','pic'] as $k) {
        if (!empty($u[$k]) && is_scalar($u[$k])) {
            if ($url = $mk($u[$k])) return $url;
        }
    }

    // 3) вольные URL внутри структуры
    $url = $this->findUrlRecursively($u, 'get-yapic');
    if ($url && $this->isGoodAvatarUrl($url)) return $url;
    $url = $this->findUrlRecursively($u, 'avatars.mds.yandex.net');
    if ($url && $this->isGoodAvatarUrl($url)) return $url;

    // 4) общий поиск yapic-id в строках
    $yid = $this->findYapicIdRecursively($u);
    if ($yid && ($yid = $this->normalizeYapicId($yid))) {
        return 'https://avatars.mds.yandex.net/get-yapic/'.$yid.'/islands-68';
    }

    return null;
}


    private function normalizeYapicId(string $s): ?string
    {
        $s = trim($s);
        if (!preg_match('~^(\d{1,6})/([A-Za-z0-9_-]{1,64})$~', $s, $m)) {
            $s = preg_replace('~[^0-9A-Za-z/_-]+~', '', $s);
            if (!preg_match('~^(\d{1,6})/([A-Za-z0-9_-]{1,64})$~', $s, $m)) {
                return null;
            }
        }
        $right = $m[2];
        if (stripos($right, 'enc-') === 0) return null;
        if (substr($right, -1) === '-')   return null;
        return $m[1].'/'.$right;
    }

    private function findYapicIdRecursively($node): ?string
    {
        if (is_string($node)) {
            $node = trim($node);
            if (preg_match('~\b(\d{1,6}/(?!enc-)[A-Za-z0-9_-]{1,64})\b~u', $node, $m)) {
                if (substr($m[1], -1) !== '-') return $m[1];
            }
            return null;
        }
        if (is_array($node)) {
            foreach ($node as $v) {
                if ($id = $this->findYapicIdRecursively($v)) return $id;
            }
        }
        return null;
    }

    private function findUrlRecursively($node, string $must_contain = ''): ?string
    {
        if (is_string($node)) {
            $s = trim($node);
            if (stripos($s, 'http') === 0) {
                if ($must_contain === '' || stripos($s, $must_contain) !== false) {
                    return $s;
                }
            }
            if (preg_match('~https?://[^\s"\')]+~u', $s, $m)) {
                $u = $m[0];
                if ($must_contain === '' || stripos($u, $must_contain) !== false) {
                    return $u;
                }
            }
            return null;
        }
        if (is_array($node)) {
            foreach ($node as $v) {
                if ($u = $this->findUrlRecursively($v, $must_contain)) return $u;
            }
        }
        return null;
    }

    /** ===== Парсинг ответа ===== */

    private function extractReviews(array $json): array
    {
        $out = [];

        $entities = $json['entities'] ?? ($json['data']['entities'] ?? null);
        if (is_array($entities) && !empty($entities)) {
            $reviews_map = $entities['reviews'] ?? null;
            $users_map   = $entities['users']   ?? ($entities['authors'] ?? []);
            if (is_array($reviews_map)) {
                foreach ($reviews_map as $rev) {
                    $norm = $this->normalizeFromEntity($rev, $users_map);
                    if (!empty($norm['yandex_review_id'])) $out[] = $norm;
                }
            }
        }

        if (!$out) {
            $views = $json['view']['views'] ?? $json['views'] ?? null;
            if (!is_array($views)) $views = [$json];
            foreach ($views as $v) {
                $items = $v['items'] ?? $v['reviews'] ?? $v['cards'] ?? null;
                if (!is_array($items)) continue;
                foreach ($items as $it) {
                    $norm = $this->normalizeFromItem($it);
                    if (!empty($norm['yandex_review_id'])) $out[] = $norm;
                }
            }
        }

        if (!$out) {
            $out = $this->scanRecursively($json);
        }

        $uniq = []; $res = [];
        foreach ($out as $r) {
            $id = $r['yandex_review_id'];
            if (!isset($uniq[$id])) { $uniq[$id] = true; $res[] = $r; }
        }
        return $res;
    }

    private function normalizeFromEntity(array $rev, array $users_map): array
    {
        $id    = $rev['id'] ?? $rev['reviewId'] ?? $rev['hash'] ?? null;
        $text  = $rev['text'] ?? ($rev['body']['text'] ?? ($rev['content']['text'] ?? null));

        $rate = null;
        if (array_key_exists('rating', $rev)) $rate = $this->normalizeRatingValue($rev['rating']);
        if ($rate === null && isset($rev['ratings'])) $rate = $this->normalizeRatingValue($rev['ratings']);

        $per   = $rev['permalink'] ?? ($rev['url'] ?? null);
        $dtraw = $rev['date'] ?? ($rev['time'] ?? ($rev['publishTime'] ?? null));
        $dt    = $this->normalizeDateValue($dtraw);

        $author_name = null;
        $author_avatar = null;

        $uid = $rev['userId'] ?? $rev['authorId'] ?? null;
        if ($uid && isset($users_map[$uid]) && is_array($users_map[$uid])) {
            $u = $users_map[$uid];
            $author_name   = $u['name'] ?? ($u['displayName'] ?? ($u['login'] ?? null));
            $author_avatar = $this->extractAvatarFromUser($u);
        }

        if ($author_avatar === null && isset($rev['author']) && is_array($rev['author'])) {
            $author_avatar = $this->extractAvatarFromUser($rev['author']);
            if (!$author_name) {
                $author_name = $rev['author']['name'] ?? ($rev['author']['displayName'] ?? null);
            }
        }
        if ($author_avatar === null && isset($rev['user']) && is_array($rev['user'])) {
            $author_avatar = $this->extractAvatarFromUser($rev['user']);
            if (!$author_name) {
                $author_name = $rev['user']['name'] ?? ($rev['user']['displayName'] ?? null);
            }
        }

        return [
            'yandex_review_id' => $id ? (string)$id : '',
            'author_name'      => $author_name,
            'author_avatar'    => $author_avatar,
            'rating'           => $rate,
            'text'             => $text,
            'photos'           => $this->extractPhotosFromReview($rev),
            'permalink'        => $per,
            'review_datetime'  => $dt,
        ];
    }

    private function normalizeFromItem(array $it): array
    {
        $id   = $it['id'] ?? $it['reviewId'] ?? $it['hash'] ?? null;

        $rate = null;
        if (array_key_exists('rating', $it)) $rate = $this->normalizeRatingValue($it['rating']);
        if ($rate === null && isset($it['ratings'])) $rate = $this->normalizeRatingValue($it['ratings']);

        $text = $it['text'] ?? ($it['content']['text'] ?? null);
        $per  = $it['permalink'] ?? ($it['url'] ?? null);
        $dtraw= $it['date'] ?? ($it['time'] ?? ($it['publishTime'] ?? null));
        $dt   = $this->normalizeDateValue($dtraw);

        $author_name = $it['author']['name'] ?? ($it['user']['name'] ?? ($it['author']['displayName'] ?? ($it['user']['displayName'] ?? null)));

        $author_avatar = null;
        if (!empty($it['author']) && is_array($it['author'])) {
            $author_avatar = $this->extractAvatarFromUser($it['author']);
        }
        if ($author_avatar === null && !empty($it['user']) && is_array($it['user'])) {
            $author_avatar = $this->extractAvatarFromUser($it['user']);
        }

        return [
            'yandex_review_id' => $id ? (string)$id : '',
            'author_name'      => $author_name,
            'author_avatar'    => $author_avatar,
            'rating'           => $rate,
            'text'             => $text,
            'photos'           => $this->extractPhotosFromReview($it),
            'permalink'        => $per,
            'review_datetime'  => $dt,
        ];
    }

    private function extractPhotosFromReview(array $rev): array
    {
        $candidates = [];
        $fields = ['photos', 'images', 'media', 'attachments', 'gallery', 'pictures'];
        $found_fields = [];

        foreach ($fields as $key) {
            if (array_key_exists($key, $rev)) {
                $found_fields[] = $key;
            }
            if (!empty($rev[$key])) {
                $candidates = array_merge($candidates, $this->collectPhotoUrls($rev[$key]));
            }
        }

        if (!empty($rev['content']) && is_array($rev['content'])) {
            foreach ($fields as $key) {
                if (array_key_exists($key, $rev['content'])) {
                    $found_fields[] = 'content.'.$key;
                }
                if (!empty($rev['content'][$key])) {
                    $candidates = array_merge($candidates, $this->collectPhotoUrls($rev['content'][$key]));
                }
            }
        }

        if ($found_fields) {
            $this->logPhotoDiagnostics($rev, $found_fields);
        }

        $uniq = [];
        $out = [];
        foreach ($candidates as $url) {
            $url = $this->normalizePhotoUrl($url);
            if (!$url) {
                continue;
            }
            if (!isset($uniq[$url])) {
                $uniq[$url] = true;
                $out[] = $url;
            }
        }

        return $out;
    }

    private function logPhotoDiagnostics(array $review, array $fields): void
    {
        if ($this->photo_debug_logged) {
            return;
        }

        $this->photo_debug_logged = true;

        $fields = array_values(array_unique($fields));
        $snippet = json_encode($review, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        if (!is_string($snippet)) {
            $snippet = var_export($review, true);
        }

        if (function_exists('mb_substr')) {
            $snippet = mb_substr($snippet, 0, 2048, 'UTF-8');
        } else {
            $snippet = substr($snippet, 0, 2048);
        }

        waLog::log(
            'YandexReviewsClient photo fields: '.implode(', ', $fields).'; review fragment: '.$snippet,
            'yandexreviews.log'
        );
    }

    private function collectPhotoUrls($node): array
    {
        $out = [];
        if (is_string($node)) {
            $out[] = $node;
            return $out;
        }

        if (!is_array($node)) {
            return $out;
        }

        $url_keys = [
            'url', 'orig', 'original', 'src', 'href', 'image', 'imageUrl', 'image_url',
            'photo', 'photoUrl', 'photo_url', 'preview', 'previewUrl', 'preview_url'
        ];

        foreach ($url_keys as $k) {
            if (isset($node[$k]) && is_string($node[$k])) {
                $out[] = $node[$k];
            }
        }

        foreach ($node as $v) {
            if (is_array($v) || is_string($v)) {
                $out = array_merge($out, $this->collectPhotoUrls($v));
            }
        }

        return $out;
    }

    private function normalizePhotoUrl($url): ?string
    {
        $url = trim((string)$url);
        if ($url === '') {
            return null;
        }

        if (strpos($url, '//') === 0) {
            $url = 'https:' . $url;
        }

        if (strpos($url, '/') === 0) {
            $url = 'https://avatars.mds.yandex.net' . $url;
        }

        if (stripos($url, 'http') !== 0) {
            return null;
        }

        if (strpos($url, '/enc-') !== false) {
            return null;
        }

        if (preg_match('~\\.(jpg|jpeg|png|webp)(?:\\?.*)?$~i', $url)) {
            return $url;
        }

        if (stripos($url, 'yandex.net/get-') !== false || stripos($url, 'yandex.ru/get-') !== false) {
            return $url;
        }

        if (stripos($url, 'avatars.mds.yandex.net/') !== false) {
            return $url;
        }

        return null;
    }

    private function scanRecursively($node): array
    {
        $out = [];
        if (is_array($node)) {
            $is_list = array_keys($node) === range(0, count($node) - 1);
            if ($is_list) {
                foreach ($node as $it) {
                    if (is_array($it)) {
                        $n = $this->normalizeFromItem($it);
                        if (!empty($n['yandex_review_id']) && ($n['rating'] !== null || !empty($n['text']))) {
                            $out[] = $n;
                        } else {
                            $out = array_merge($out, $this->scanRecursively($it));
                        }
                    }
                }
            } else {
                foreach ($node as $v) {
                    if (is_array($v)) {
                        $out = array_merge($out, $this->scanRecursively($v));
                    }
                }
            }
        }
        return $out;
    }
}
