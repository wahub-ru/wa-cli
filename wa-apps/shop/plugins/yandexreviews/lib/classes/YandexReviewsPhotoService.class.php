<?php

class YandexReviewsPhotoService
{
    private const MAX_PHOTOS = 10;

    /** Скачивает и сохраняет фото в wa-data/public; возвращает относительный путь или null */
    public function downloadAndStore($company_id, $remote_url)
    {
        $remote_url = trim((string)$remote_url);
        if ($remote_url === '') {
            return null;
        }

        $ch = curl_init($remote_url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_TIMEOUT        => 25,
            CURLOPT_CONNECTTIMEOUT => 7,
            CURLOPT_USERAGENT      => 'Mozilla/5.0',
            CURLOPT_REFERER        => 'https://yandex.ru/maps/',
            CURLOPT_ENCODING       => '',
            CURLOPT_IPRESOLVE      => CURL_IPRESOLVE_V4,
        ]);
        $bin = curl_exec($ch);
        $err = curl_error($ch);
        $code= curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $ct  = (string) curl_getinfo($ch, CURLINFO_CONTENT_TYPE);
        curl_close($ch);

        if ($err || $code >= 400 || !$bin) {
            waLog::log("Photo download fail: code={$code} err={$err} url={$remote_url}", 'yandexreviews.log');
            return null;
        }

        $ext = $this->detectExtension($remote_url, $ct);
        $hash = sha1($remote_url);

        $rel_dir = "plugins/yandexreviews/photos/{$company_id}/";
        $rel_path = $rel_dir.$hash.$ext;

        $abs_dir = wa()->getDataPath($rel_dir, true, 'shop');
        $abs = $abs_dir.$hash.$ext;

        if (file_exists($abs) && filesize($abs) > 0) {
            @chmod($abs, 0644);
            return $rel_path;
        }

        $tmp = tempnam(sys_get_temp_dir(), 'yaphoto');
        file_put_contents($tmp, $bin);

        if (@rename($tmp, $abs) === false) {
            if (!@copy($tmp, $abs)) {
                waLog::log("Photo store fail: url={$remote_url}", 'yandexreviews.log');
                @unlink($tmp);
                return null;
            }
            @unlink($tmp);
        }

        @chmod($abs, 0644);
        return $rel_path;
    }

    public function normalizePhotos($photos_json): array
    {
        if (!$photos_json) {
            return [];
        }

        $data = json_decode((string)$photos_json, true);
        if (!is_array($data)) {
            return [];
        }

        $out = [];
        foreach ($data as $item) {
            if (is_string($item)) {
                $url = trim($item);
                if ($url !== '') {
                    $out[] = ['url' => $url, 'local' => null];
                }
                continue;
            }
            if (!is_array($item)) {
                continue;
            }

            $url = isset($item['url']) ? trim((string)$item['url']) : '';
            $local = isset($item['local']) ? trim((string)$item['local']) : '';

            if ($url === '' && isset($item['src'])) {
                $url = trim((string)$item['src']);
            }
            if ($local === '' && isset($item['path'])) {
                $local = trim((string)$item['path']);
            }

            if ($url === '' && $local === '') {
                continue;
            }

            $out[] = [
                'url'   => $url !== '' ? $url : null,
                'local' => $local !== '' ? $local : null,
            ];
        }

        return $out;
    }

    public function buildPublicUrls(array $photos): array
    {
        $out = [];
        foreach ($photos as $p) {
            if (!is_array($p)) {
                continue;
            }
            $url = null;
            if (!empty($p['local'])) {
                $abs = wa()->getDataPath($p['local'], true, 'shop');
                if (!file_exists($abs)) {
                    waLog::log("Photo local missing: {$abs}", 'yandexreviews.log');
                } elseif (!is_readable($abs)) {
                    @chmod($abs, 0644);
                    if (!is_readable($abs)) {
                        waLog::log("Photo local unreadable: {$abs}", 'yandexreviews.log');
                    }
                }
                $url = wa()->getDataUrl($p['local'], true, 'shop');
                if (!$url) {
                    waLog::log("Photo public url empty: local={$p['local']}", 'yandexreviews.log');
                }
            } elseif (!empty($p['url'])) {
                $url = (string)$p['url'];
            }
            if ($url) {
                $out[] = $url;
            }
        }

        return $out;
    }

    public function preparePhotoItems(array $urls, $company_id, array &$stats = null): array
    {
        $urls = array_values(array_unique(array_filter(array_map('trim', $urls))));
        $urls = array_slice($urls, 0, self::MAX_PHOTOS);

        $items = [];
        foreach ($urls as $url) {
            $local = $this->downloadAndStore($company_id, $url);
            if (is_array($stats)) {
                if ($local) {
                    $stats['photos_downloaded'] = ($stats['photos_downloaded'] ?? 0) + 1;
                } else {
                    $stats['photos_failed'] = ($stats['photos_failed'] ?? 0) + 1;
                }
            }
            $items[] = [
                'url'   => $url,
                'local' => $local,
            ];
        }

        return $items;
    }

    private function detectExtension(string $url, string $content_type): string
    {
        $ext = '';
        if (preg_match('~\.(jpg|jpeg|png|webp)(?:\?.*)?$~i', $url, $m)) {
            $ext = '.'.strtolower($m[1]);
        }

        if ($ext !== '') {
            return $ext;
        }

        if (stripos($content_type, 'image/png') === 0)  return '.png';
        if (stripos($content_type, 'image/webp') === 0) return '.webp';
        if (stripos($content_type, 'image/jpeg') === 0 || stripos($content_type, 'image/jpg') === 0) return '.jpg';

        return '.jpg';
    }
}
