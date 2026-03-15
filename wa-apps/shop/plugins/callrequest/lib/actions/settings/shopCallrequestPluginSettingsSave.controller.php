<?php

class shopCallrequestPluginSettingsSaveController extends waJsonController
{
    public function execute()
    {
        $token = waRequest::post('_csrf', '', waRequest::TYPE_STRING_TRIM);
        $user  = wa()->getUser();

        // Проверка CSRF токена
        if (method_exists($user, 'checkCsrfToken')) {
            if (!$user->checkCsrfToken($token)) {
                $this->errors[] = 'CSRF token error';
                return;
            }
        }

        $price_model = new shopCallrequestPriceModel();

        $deleted = waRequest::post('deleted', [], waRequest::TYPE_ARRAY);
        if (is_array($deleted)) {
            foreach ($deleted as $id) {
                $id_int = (int) $id;
                if ($id_int > 0) {
                    try {
                        $price_model->deleteById($id_int);
                    } catch (Exception $e) {
                        waLog::log(
                            '[callrequest] price delete failed: '.$e->getMessage(),
                            'callrequest_error.log'
                        );
                    }
                }
            }
        }


        // === ПЕРВОЕ: СОХРАНИТЬ РЕЗЕРВНУЮ КОПИЮ! ===
        $backup_prices = $price_model->getAll();

        try {
            $updatedPrices = [];
            $prices = waRequest::post('prices', [], waRequest::TYPE_ARRAY);

            foreach ($prices as $id => $row) {

                $id = (int)$id;
                if ($id <= 0) {
                    continue;
                }

                $update_data = [
                    'type'      => (string)$row['type'],
                    'size'      => (string)$row['size'],
                    'thickness' => (int)$row['thickness'],
                    'print'     => (string)$row['print'],
                    'qty'       => (int)$row['qty'],
                    'price'     => (float)$row['price']
                ];

                try {
                    $price_model->updateById($id, $update_data);
                } catch (Exception $e) {
                    waLog::log(
                        '[callrequest] price update failed: ' . $e->getMessage(),
                        'callrequest_error.log'
                    );
                }
            }


            // Обработка новых цен
            $insertedPrices = [];
            $newPrices = waRequest::post('newPrices', array(), waRequest::TYPE_ARRAY);

            // Преобразуем формат данных
            if (!empty($newPrices) && !isset($newPrices[0]['type'])) {
                $newPrices = $this->normalizeNewPrices($newPrices);
            }

            foreach ($newPrices as $np) {
                if (empty($np['size']) || trim($np['size']) === '') {
                    continue;
                }
                $insert = array(
                    'type'      => isset($np['type']) ? (string)$np['type'] : '',
                    'size'      => isset($np['size']) ? (string)$np['size'] : '',
                    'thickness' => isset($np['thickness']) ? (int)$np['thickness'] : 0,
                    'print'     => isset($np['print']) ? (string)$np['print'] : '',
                    'qty'       => isset($np['qty']) ? (int)$np['qty'] : 0,
                    'price'     => isset($np['price']) ? (float)$np['price'] : 0.0,
                );
                $id = $price_model->insert($insert);
                if ($id) {
                    $insert['id'] = $id;
                    $insertedPrices[] = $insert;
                    waLog::log('Inserted new price with ID: ' . $id, 'shop/plugins/callrequest/debug.log');
                }
            }

            // Сохранение основных настроек плагина
            $data = [
                'enabled'        => (int)(bool)waRequest::post('enabled', 1, waRequest::TYPE_INT),
                'trigger_class'  => (string)waRequest::post('trigger_class', 'callrequest-open', waRequest::TYPE_STRING_TRIM),
                'email_to'   => (string)waRequest::post('email_to', '', waRequest::TYPE_STRING_TRIM),

                'policy_enabled' => (int)(bool)waRequest::post('policy_enabled', 0, waRequest::TYPE_INT),
                'policy_html'    => (string)waRequest::post('policy_html', '', waRequest::TYPE_STRING_TRIM),

                'btn_color'      => (string)waRequest::post('btn_color', '#2ecc71', waRequest::TYPE_STRING_TRIM),
                'btn_text_color' => (string)waRequest::post('btn_text_color', '#ffffff', waRequest::TYPE_STRING_TRIM),

                'success_text'   => (string)waRequest::post('success_text', '', waRequest::TYPE_STRING_TRIM),
                'phone_mask'     => (string)waRequest::post('phone_mask', '', waRequest::TYPE_STRING_TRIM),
            ];

            $m   = new waAppSettingsModel();
            $app = 'shop';
            foreach ($data as $k => $v) {
                $m->set($app, 'plugins.callrequest.'.$k, $v);
                $m->set($app, 'plugin.callrequest.'.$k, $v);
            }

            // Формирование полного ответа
            $this->response = [
                'status' => 'ok',
                'saved' => true,
                'settings' => $data,
                'updated_prices' => $updatedPrices,
                'new_prices' => $insertedPrices,
                'summary' => [
                    'settings_saved' => count($data),
                    'prices_updated' => count($updatedPrices),
                    'prices_added' => count($insertedPrices),
                    'backup_count' => count($backup_prices)
                ]
            ];

        } catch (Exception $e) {
            // Восстановление из резервной копии при ошибке
            $this->restoreBackup($price_model, $backup_prices);

            waLog::log('Error saving prices: ' . $e->getMessage(), 'shop/plugins/callrequest/error.log');
            $this->errors[] = 'Ошибка сохранения: ' . $e->getMessage();
        }
    }

    /**
     * Безопасное обновление записи
     */
    private function updatePriceRecord($model, $id, $data)
    {
        // Вариант 1: Используем прямой SQL (самый безопасный)
        $sql = "UPDATE `shop_callrequest_prices` 
                SET `type` = :type, 
                    `size` = :size, 
                    `thickness` = :thickness, 
                    `print` = :print, 
                    `qty` = :qty, 
                    `price` = :price 
                WHERE `id` = :id";

        $params = array_merge($data, ['id' => $id]);

        // Используем exec вместо query для UPDATE
        $result = $model->exec($sql, $params);

        // Или вариант 2: Проверенный метод updateByField
        // return $model->updateByField('id', $id, $data);

        return $result !== false;
    }

    /**
     * Преобразование формата новых цен
     */
    private function normalizeNewPrices($new_prices)
    {
        $result = [];
        $count = count($new_prices);
        $fields_per_row = 6; // type, size, thickness, print, qty, price

        for ($i = 0; $i < $count; $i += $fields_per_row) {
            $row = [];

            if (isset($new_prices[$i+0])) $row['type'] = (string) $new_prices[$i+0];
            if (isset($new_prices[$i+1])) $row['size'] = (string) $new_prices[$i+1];
            if (isset($new_prices[$i+2])) $row['thickness'] = (int) $new_prices[$i+2];
            if (isset($new_prices[$i+3])) $row['print'] = (string) $new_prices[$i+3];
            if (isset($new_prices[$i+4])) $row['qty'] = (int) $new_prices[$i+4];
            if (isset($new_prices[$i+5])) $row['price'] = (string) $new_prices[$i+5];

            $result[] = $row;
        }

        return $result;
    }

    /**
     * Восстановление из резервной копии
     */
    private function restoreBackup($model, $backup_prices)
    {
        try {
            // Очищаем таблицу
            $model->exec("DELETE FROM `shop_callrequest_prices`");

            // Восстанавливаем данные
            foreach ($backup_prices as $price) {
                $model->insert([
                    'type'      => $price['type'],
                    'size'      => $price['size'],
                    'thickness' => (int)$price['thickness'],
                    'print'     => $price['print'],
                    'qty'       => (int)$price['qty'],
                    'price'     => (float)$price['price']
                ]);
            }

            waLog::log('Restored ' . count($backup_prices) . ' records from backup', 'shop/plugins/callrequest/debug.log');

        } catch (Exception $e) {
            waLog::log('Failed to restore backup: ' . $e->getMessage(), 'shop/plugins/callrequest/error.log');
        }
    }
}