<?php

class shopCallrequestPriceModel extends waModel
{
    protected $table = 'shop_callrequest_prices';

    public function getAll()
    {
        return $this->query(
            "SELECT * FROM `".$this->table."` ORDER BY `type`, `size`, `thickness`, `print`, `qty`"
        )->fetchAll();
    }

    /**
     * Безопасное обновление по ID
     */
    public function updateById($id, $data)
    {
        $id = (int)$id;
        if ($id <= 0 || empty($data)) {
            return false;
        }

        // Убираем ID из данных, если он там есть
        if (isset($data['id'])) {
            unset($data['id']);
        }

        // Используем прямой SQL для безопасности
        $set_parts = [];
        $params = [];

        foreach ($data as $field => $value) {
            $set_parts[] = "`{$field}` = :{$field}";
            $params[$field] = $value;
        }

        $params['id'] = $id;
        $sql = "UPDATE `{$this->table}` 
                SET " . implode(', ', $set_parts) . " 
                WHERE `id` = :id";

        return $this->exec($sql, $params);
    }

    /**
     * Обновить несколько записей
     */
    public function updateMultiple($data_array)
    {
        foreach ($data_array as $id => $data) {
            $this->updateById($id, $data);
        }
        return true;
    }
}