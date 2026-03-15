<?php

class shopTgconsultPluginFrontendWebhookController extends waController
{
    public function execute()
    {
        $raw = file_get_contents('php://input');
        // на проде можно выключить, сейчас оставим
        waLog::log('RAW: '.$raw, 'tgconsult.log');

        $u = json_decode($raw, true);
        if (!is_array($u)) return $this->ok();

        $msg = $u['message'] ?? $u['edited_message'] ?? $u['channel_post'] ?? null;
        if (!$msg || !is_array($msg)) return $this->ok();

        // ищем тег в reply_to_message
        $reply   = $msg['reply_to_message'] ?? null;
        $carrier = '';
        if ($reply) {
            $carrier = (string)($reply['text'] ?? '');
            if ($carrier === '') $carrier = (string)($reply['caption'] ?? '');
        }
        // запасной вариант — менеджер мог вставить тег в сам ответ
        if ($carrier === '') {
            $carrier = (string)($msg['text'] ?? '');
            if ($carrier === '') $carrier = (string)($msg['caption'] ?? '');
        }
        if ($carrier === '') return $this->ok();

        if (!preg_match('~\[\[tgc:([a-z0-9]{16,64}):(\d+)\]\]~i', $carrier, $m)) {
            waLog::log('NO TAG in reply', 'tgconsult.log');
            return $this->ok();
        }
        $chat_token = (string) $m[1];

        $text = trim((string)(($msg['text'] ?? '') !== '' ? $msg['text'] : ($msg['caption'] ?? '')));
        if ($text === '') return $this->ok();
        if (!shopTgconsultPlugin::ensureSchema()) return $this->ok();

        try {
            $db  = new waModel();
            $now = date('Y-m-d H:i:s');

            $chat = $db->query(
                "SELECT * FROM shop_tgconsult_chat WHERE token = s:tok LIMIT 1",
                ['tok'=>$chat_token]
            )->fetchAssoc();

            if ($chat) {
                $db->exec(
                    "INSERT INTO shop_tgconsult_message (chat_id, sender, text, created)
                     VALUES (i:cid, 'manager', s:txt, s:now)",
                    ['cid'=>(int)$chat['id'], 'txt'=>$text, 'now'=>$now]
                );
                $db->exec(
                    "UPDATE shop_tgconsult_chat SET updated = s:now WHERE id = i:id",
                    ['now'=>$now, 'id'=>(int)$chat['id']]
                );
                waLog::log("IN << chat#{$chat['id']}: ".$text, 'tgconsult.log');
            } else {
                waLog::log('NO CHAT for token '.$chat_token, 'tgconsult.log');
            }
        } catch (Exception $e) {
            waLog::log('WEBHOOK ERROR: '.$e->getMessage(), 'tgconsult.log');
        }

        return $this->ok();
    }

    private function ok()
    {
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(['ok' => true]);
    }
}
