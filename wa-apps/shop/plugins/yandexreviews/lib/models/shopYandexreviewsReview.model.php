<?php

class shopYandexreviewsReviewModel extends waModel
{
    protected $table = 'shop_yandexreviews_review';

    public function getPaged($company_id, $offset, $limit, $hide_low, $sort)
    {
        $where = 'company_id = i:cid';
        if ($hide_low) {
            $where .= ' AND (rating IS NULL OR rating >= 4)';
        }

        switch ($sort) {
            case 'date_asc':
                $order = ' review_datetime ASC, id ASC ';
                break;
            case 'rating_desc':
                $order = ' (rating IS NULL), rating DESC, review_datetime DESC, id DESC ';
                break;
            case 'rating_asc':
                $order = ' (rating IS NULL), rating ASC, review_datetime DESC, id DESC ';
                break;
            case 'date_desc':
            default:
                $order = ' review_datetime DESC, id DESC ';
        }

        $sql = "SELECT * FROM {$this->table}
                WHERE {$where}
                ORDER BY {$order}
                LIMIT i:lim OFFSET i:off";

        return $this->query($sql, [
            'cid' => (int)$company_id,
            'lim' => (int)$limit,
            'off' => (int)$offset,
        ])->fetchAll();
    }

    public function countAvailable($company_id, $hide_low)
    {
        $where = 'company_id = i:cid';
        if ($hide_low) {
            $where .= ' AND (rating IS NULL OR rating >= 4)';
        }
        $sql = "SELECT COUNT(*) cnt FROM {$this->table} WHERE {$where}";
        return (int)$this->query($sql, ['cid' => (int)$company_id])->fetchField('cnt');
    }

    public function countAll()
    {
        $sql = "SELECT COUNT(*) cnt FROM {$this->table}";
        return (int)$this->query($sql)->fetchField('cnt');
    }
}
