<?php

class YandexReviewsAvatarService
{
    /** Скачивает и сохраняет в wa-data/public; возвращает относительное имя файла или null */
    public function downloadAndStore($company_id, $remote_url)
    {
        $remote_url = trim((string)$remote_url);
        if ($remote_url === '') {
            return null;
        }

        // Скачаем
        $ch = curl_init($remote_url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_TIMEOUT        => 20,
            CURLOPT_CONNECTTIMEOUT => 7,
            CURLOPT_USERAGENT      => 'Mozilla/5.0',
            CURLOPT_REFERER        => 'https://yandex.ru/maps/',
            CURLOPT_ENCODING       => '',
            CURLOPT_IPRESOLVE      => CURL_IPRESOLVE_V4, // важный момент для MDS
        ]);
        $bin = curl_exec($ch);
        $err = curl_error($ch);
        $code= curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $ct  = (string) curl_getinfo($ch, CURLINFO_CONTENT_TYPE);
        curl_close($ch);

        if ($err || $code >= 400 || !$bin) {
            waLog::log("Avatar download fail: code=$code err=$err url=$remote_url", 'yandexreviews.log');
            return null;
        }

        // Выберем расширение по content-type (по умолчанию jpg)
        $ext = '.jpg';
        if (stripos($ct, 'image/png') === 0)  $ext = '.png';
        if (stripos($ct, 'image/webp') === 0) $ext = '.webp';
        if (stripos($ct, 'image/jpeg') === 0 || stripos($ct, 'image/jpg') === 0) $ext = '.jpg';

        $hash    = sha1($remote_url);
        $rel_dir = "plugins/yandexreviews/avatars/{$company_id}/";
        $rel_name= "{$hash}{$ext}";

        $abs_dir = wa()->getDataPath($rel_dir, true, 'shop'); // создаёт каталог
        $abs     = $abs_dir . $rel_name;

        // Уже есть?
        if (file_exists($abs) && filesize($abs) > 0) {
            return $rel_name;
        }

        // Сохраним оригинал во временный файл
        $tmp = tempnam(sys_get_temp_dir(), 'yaava');
        file_put_contents($tmp, $bin);

        // Попробуем через waImage привести к 68x68 (если движок умеет этот формат)
        $ok = false;
        try {
            $img = waImage::factory($tmp);
            $img->resize(68, 68, waImage::INVERSE)->crop(68, 68)->save($abs, 90);
            $ok = true;
        } catch (Exception $e) {
            // Если, например, нет WEBP поддержки — сохраним как есть, без ресайза
            try {
                if (@rename($tmp, $abs) === false) {
                    // Фолбэк — копированием
                    if (!@copy($tmp, $abs)) {
                        waLog::log("Avatar image error and copy fail: ".$e->getMessage(), 'yandexreviews.log');
                        @unlink($tmp);
                        return null;
                    }
                    @unlink($tmp);
                } else {
                    // переименовали ok
                }
                $ok = true;
            } catch (Exception $e2) {
                waLog::log("Avatar store fallback fail: ".$e2->getMessage(), 'yandexreviews.log');
                @unlink($tmp);
                return null;
            }
        }

        if (!$ok) {
            @unlink($tmp);
            return null;
        }

        return $rel_name;
    }

    /** Публичный URL для сохранённого файла */
    public function getPublicUrl($company_id, $rel_name)
    {
        if (!$rel_name) return null;
        $rel_dir = "plugins/yandexreviews/avatars/{$company_id}/";
        return wa()->getDataUrl($rel_dir.$rel_name, true, 'shop');
    }
}
