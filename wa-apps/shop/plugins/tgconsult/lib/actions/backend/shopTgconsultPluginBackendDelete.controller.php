<?php

class shopTgconsultPluginBackendDeleteController extends waJsonController
{
    public function execute()
    {
        $chat_id = (int) waRequest::post('id', 0, waRequest::TYPE_INT);
        if (!$chat_id) { $this->errors[] = 'chat_id required'; return; }
        if (!shopTgconsultPlugin::ensureSchema()) { $this->errors[] = 'Чат временно недоступен'; return; }

        // CSRF (если метод доступен — проверим)
        $csrf = (string) waRequest::post('_csrf', '', waRequest::TYPE_STRING_TRIM);
        if (method_exists(wa(), 'getCsrfToken')) {
            if ($csrf === '' || $csrf !== wa()->getCsrfToken()) {
                $this->errors[] = 'CSRF error';
                return;
            }
        }

        $db = new waModel();
        try {
            $db->exec('START TRANSACTION');

            // удаляем сообщения
            $db->exec(
                "DELETE FROM shop_tgconsult_message WHERE chat_id = i:cid",
                ['cid' => $chat_id]
            );

            // удаляем сам чат
            $db->exec(
                "DELETE FROM shop_tgconsult_chat WHERE id = i:cid",
                ['cid' => $chat_id]
            );

            $db->exec('COMMIT');

            $this->response = ['status' => 'ok', 'id' => $chat_id];
        } catch (Exception $e) {
            try { $db->exec('ROLLBACK'); } catch (Exception $e2) {}
            $this->errors[] = 'DB error: '.$e->getMessage();
        }
    }
}
