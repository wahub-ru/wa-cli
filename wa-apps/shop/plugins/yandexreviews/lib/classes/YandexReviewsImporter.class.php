<?php

class YandexReviewsImporter
{
    /**
     * Импортирует не более $batch_limit НОВЫХ отзывов. Идёт страницами.
     * Дополнительно: если отзыв уже есть в БД, но у него нет аватарки,
     * а в свежем ответе она появилась — заполняем и докачиваем локально.
     */
    public function runBatch($company, $yandex_company_id, $batch_limit = 30, $referer_url = null)
    {
        $rm = new shopYandexreviewsReviewModel();

        $batch_limit = max(1, (int)$batch_limit);
        $page_size   = min(50, $batch_limit);

        $client = new YandexReviewsClient();
        $avatar = new YandexReviewsAvatarService();
        $photos = new YandexReviewsPhotoService();

        $inserted = 0;
        $scanned  = 0;
        $pages    = 0;
        $offset   = 0;
        $photo_stats = [
            'reviews_with_photos' => 0,
            'photos_total'        => 0,
            'photos_downloaded'   => 0,
            'photos_failed'       => 0,
            'photos_skipped'      => 0,
            'skipped_existing'    => 0,
            'skipped_duplicates'  => 0,
            'skipped_limit'       => 0,
            'skipped_empty'       => 0,
        ];
        $photo_log_count = 0;

        while ($inserted < $batch_limit) {
            $pages++;
            $list = $client->fetch($yandex_company_id, $page_size, $referer_url, $offset);
            if (!$list) {
                break;
            }

            foreach ($list as $r) {
                $scanned++;

                $rid = (string)($r['yandex_review_id'] ?? '');
                if ($rid === '') continue;

                if (!empty($r['photos']) && $photo_log_count < 3) {
                    $photo_log_count++;
                    $sample = array_slice(array_values($r['photos']), 0, 3);
                    waLog::log(
                        'Importer photo debug: review_id=' . $rid . ' photos=' . count($r['photos'])
                        . ' sample=' . json_encode($sample, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE),
                        'yandexreviews.log'
                    );
                }

                $exists = $rm->getByField([
                    'company_id'       => (int)$company['id'],
                    'yandex_review_id' => $rid,
                ]);

                if ($exists) {
                    // ДОЗАПОЛНИМ АВАТАР, если был пустой
                    if (empty($exists['author_avatar']) && !empty($r['author_avatar'])) {
                        $upd = ['author_avatar' => $r['author_avatar']];
                        $rm->updateById($exists['id'], $upd);

                        // качаем локально
                        $fname = $avatar->downloadAndStore((int)$company['id'], $r['author_avatar']);
                        if ($fname) {
                            $rm->updateById($exists['id'], ['author_avatar_local' => $fname]);
                        }
                    }

                    // ДОЗАПОЛНИМ ФОТО, если раньше их не было
                    if (!empty($r['photos'])) {
                        $photo_stats['reviews_with_photos']++;
                        $photo_stats['photos_total'] += count($r['photos']);
                        $current_photos = $photos->normalizePhotos($exists['photos_json'] ?? '');
                        $current_urls = [];
                        foreach ($current_photos as $p) {
                            if (!empty($p['url'])) {
                                $current_urls[$p['url']] = true;
                            }
                        }

                        $new_urls = [];
                        foreach ($r['photos'] as $u) {
                            $u = trim((string)$u);
                            if ($u === '') {
                                $photo_stats['photos_skipped']++;
                                $photo_stats['skipped_empty']++;
                                continue;
                            }
                            if (isset($current_urls[$u])) {
                                $photo_stats['photos_skipped']++;
                                $photo_stats['skipped_existing']++;
                                continue;
                            }
                            if (isset($new_urls[$u])) {
                                $photo_stats['photos_skipped']++;
                                $photo_stats['skipped_duplicates']++;
                                continue;
                            }
                            $new_urls[$u] = true;
                        }
                        $new_urls = array_keys($new_urls);

                        $changed = false;

                        if ($current_photos) {
                            foreach ($current_photos as &$photo_row) {
                                if (empty($photo_row['local']) && !empty($photo_row['url'])) {
                                    $local = $photos->downloadAndStore((int)$company['id'], $photo_row['url']);
                                    if ($local) {
                                        $photo_row['local'] = $local;
                                        $photo_stats['photos_downloaded']++;
                                        $changed = true;
                                    } else {
                                        $photo_stats['photos_failed']++;
                                    }
                                }
                            }
                            unset($photo_row);
                        }

                        if ($new_urls) {
                            $limit = max(0, 10 - count($current_photos));
                            if ($limit > 0) {
                                $new_urls = array_slice($new_urls, 0, $limit);
                                $add_items = $photos->preparePhotoItems($new_urls, (int)$company['id'], $photo_stats);
                                if ($add_items) {
                                    $current_photos = array_merge($current_photos, $add_items);
                                    $changed = true;
                                }
                            } else {
                                $photo_stats['photos_skipped'] += count($new_urls);
                                $photo_stats['skipped_limit'] += count($new_urls);
                            }
                        }

                        if ($changed) {
                            $rm->updateById($exists['id'], [
                                'photos_json' => json_encode($current_photos, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE),
                            ]);
                        }
                    }
                    continue;
                }

                // новая запись
                $row = [
                    'company_id'          => (int)$company['id'],
                    'yandex_review_id'    => $rid,
                    'author_name'         => (string)($r['author_name'] ?? null),
                    'author_avatar'       => (string)($r['author_avatar'] ?? null),
                    'author_avatar_local' => null,
                    'rating'              => isset($r['rating']) ? (int)$r['rating'] : null,
                    'text'                => (string)($r['text'] ?? ''),
                    'photos_json'         => '[]',
                    'permalink'           => (string)($r['permalink'] ?? null),
                    'review_datetime'     => !empty($r['review_datetime']) ? $r['review_datetime'] : date('Y-m-d H:i:s'),
                    'create_datetime'     => date('Y-m-d H:i:s'),
                ];

                try {
                    $new_id = $rm->insert($row);

                    if (!empty($row['author_avatar'])) {
                        $fname = $avatar->downloadAndStore((int)$company['id'], $row['author_avatar']);
                        if ($fname) {
                            $rm->updateById($new_id, ['author_avatar_local' => $fname]);
                        }
                    }

                    if (!empty($r['photos'])) {
                        $photo_stats['reviews_with_photos']++;
                        $photo_stats['photos_total'] += count($r['photos']);
                        $items = $photos->preparePhotoItems($r['photos'], (int)$company['id'], $photo_stats);
                        if ($items) {
                            $rm->updateById($new_id, [
                                'photos_json' => json_encode($items, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE),
                            ]);
                        }
                    }

                    $inserted++;
                } catch (Exception $e) {
                    waLog::log("Importer insert fail: ".$e->getMessage(), 'yandexreviews.log');
                }

                if ($inserted >= $batch_limit) break;
            }

            $offset += count($list);
            if (count($list) < $page_size) break;
        }

        waLog::log(
            "Importer batch: company={$company['id']} yid={$yandex_company_id} inserted={$inserted} scanned={$scanned} pages={$pages} "
            ."photos_reviews={$photo_stats['reviews_with_photos']} photos_total={$photo_stats['photos_total']} "
            ."photos_downloaded={$photo_stats['photos_downloaded']} photos_failed={$photo_stats['photos_failed']} "
            ."photos_skipped={$photo_stats['photos_skipped']} skipped_existing={$photo_stats['skipped_existing']} "
            ."skipped_duplicates={$photo_stats['skipped_duplicates']} skipped_limit={$photo_stats['skipped_limit']} "
            ."skipped_empty={$photo_stats['skipped_empty']}",
            'yandexreviews.log'
        );

        return ['inserted'=>$inserted, 'scanned'=>$scanned, 'pages'=>$pages];
    }
}
