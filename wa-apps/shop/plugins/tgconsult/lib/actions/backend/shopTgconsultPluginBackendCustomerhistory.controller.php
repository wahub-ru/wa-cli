<?php

class shopTgconsultPluginBackendCustomerhistoryController extends waJsonController
{
    public function execute()
    {
        $contact_id = (int) waRequest::request('customer_id', 0, waRequest::TYPE_INT);
        if ($contact_id <= 0) {
            $this->errors[] = 'Не указан покупатель';
            return;
        }

        if (!shopTgconsultPlugin::ensureSchema()) {
            $this->errors[] = 'История сообщений временно недоступна';
            return;
        }

        $customer_name = 'Покупатель';
        try {
            $contact = new waContact($contact_id);
            $formatted = trim((string) waContactNameField::formatName($contact));
            if ($formatted !== '') {
                $customer_name = $formatted;
            }
        } catch (Exception $e) {
        }

        $db = new waModel();
        $fields = shopTgconsultPlugin::messageMetaColumnExists()
            ? "m.id, m.chat_id, m.sender, m.text, m.meta, m.created"
            : "m.id, m.chat_id, m.sender, m.text, NULL AS meta, m.created";

        $rows = $db->query(
            "SELECT {$fields}, c.title AS chat_title
               FROM shop_tgconsult_chat c
               JOIN shop_tgconsult_message m ON m.chat_id = c.id
              WHERE c.customer_id = i:cid
           ORDER BY m.id ASC",
            ['cid' => $contact_id]
        )->fetchAll();

        $settings = shopTgconsultPlugin::pluginSettings();
        $messages = [];
        foreach ($rows as $row) {
            $ts = !empty($row['created']) ? (int) strtotime((string) $row['created']) : 0;
            $chat_id = (int) $row['chat_id'];
            $chat_title = trim((string) ifset($row, 'chat_title', ''));
            $messages[] = [
                'id' => (int) $row['id'],
                'chat_id' => $chat_id,
                'chat_label' => ($chat_title !== '') ? $chat_title : ('Диалог #' . $chat_id),
                'sender' => (string) $row['sender'],
                'author_name' => ($row['sender'] === 'manager')
                    ? shopTgconsultPlugin::resolveManagerAuthorName($row, $settings)
                    : $customer_name,
                'text' => (string) $row['text'],
                'created' => $ts ? date('d.m.Y H:i', $ts) : '',
                'created_raw' => (string) ifset($row, 'created', ''),
                'created_ts' => $ts,
                'created_date' => $ts ? date('Y-m-d', $ts) : '',
            ];
        }

        $this->response = [
            'status' => 'ok',
            'customer_id' => $contact_id,
            'customer_name' => $customer_name,
            'messages' => $messages,
        ];
    }
}
