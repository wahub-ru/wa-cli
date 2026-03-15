<?php

class shopTgconsultPluginBackendMarkreadController extends waJsonController
{
    public function execute()
    {
        $chat_id = (int) waRequest::post('chat_id', 0, waRequest::TYPE_INT);
        if ($chat_id <= 0) {
            $this->errors[] = 'Не указан диалог';
            return;
        }

        if (!shopTgconsultPlugin::ensureSchema()) {
            $this->errors[] = 'Чат временно недоступен';
            return;
        }

        if (!shopTgconsultPlugin::chatReadMarkerColumnExists()) {
            $this->errors[] = 'Не удалось обновить статус прочтения';
            return;
        }

        $db = new waModel();
        $chat_exists = (int) $db->query(
            "SELECT COUNT(*) FROM shop_tgconsult_chat WHERE id = i:id",
            ['id' => $chat_id]
        )->fetchField();
        if ($chat_exists <= 0) {
            $this->errors[] = 'Диалог не найден';
            return;
        }

        $last_visitor_id = (int) $db->query(
            "SELECT MAX(id)
               FROM shop_tgconsult_message
              WHERE chat_id = i:cid
                AND sender = 'visitor'",
            ['cid' => $chat_id]
        )->fetchField();

        $db->exec(
            "UPDATE shop_tgconsult_chat
                SET last_read_visitor_id = i:last_read
              WHERE id = i:id",
            ['last_read' => $last_visitor_id, 'id' => $chat_id]
        );

        $this->response = [
            'status' => 'ok',
            'chat_id' => $chat_id,
            'last_read_visitor_id' => $last_visitor_id,
            'unread' => false,
        ];
    }
}
