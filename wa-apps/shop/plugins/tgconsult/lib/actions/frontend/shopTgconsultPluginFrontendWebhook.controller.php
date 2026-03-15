<?php

class shopTgconsultPluginFrontendWebhookController extends waController
{
    public function execute()
    {
        // Логируем «сырое» тело для отладки
        $raw = file_get_contents('php://input');
        try {
            waLog::log('[webhook] RAW: '.$raw, 'tgconsult.log');
        } catch (Exception $e) {}

        $u = json_decode($raw, true);
        if (!is_array($u)) {
            return $this->ok();
        }

        // сообщение может приехать в разных полях
        $msg = $u['message'] ?? $u['edited_message'] ?? $u['channel_post'] ?? null;
        if (!$msg || !is_array($msg)) {
            return $this->ok();
        }

        // 1) Ищем «переносчик» с тегом [[tgc:...]]
        $carrier = '';

        // основной вариант — ответ реплаем на наше сообщение
        if (!empty($msg['reply_to_message'])) {
            $reply = $msg['reply_to_message'];
            $carrier = (string)($reply['text'] ?? '');
            if ($carrier === '') {
                $carrier = (string)($reply['caption'] ?? '');
            }
        }

        // запасной вариант — вдруг менеджер ответил не реплаем, а скопировал тег в своё сообщение
        if ($carrier === '') {
            $carrier = (string)($msg['text'] ?? '');
            if ($carrier === '') {
                $carrier = (string)($msg['caption'] ?? '');
            }
        }

        if ($carrier === '') {
            return $this->ok();
        }

        // 2) Вытаскиваем маркер [[tgc:token:msg_id]]
        if (!preg_match('~\[\[tgc:([a-f0-9]{32}):(\d+)]]~i', $carrier, $m)) {
            try {
                waLog::log('[webhook] NO TAG in carrier: '.$carrier, 'tgconsult.log');
            } catch (Exception $e) {}
            return $this->ok();
        }

        $chat_token = $m[1];

        // 3) Сам текст ответа менеджера
        $text = trim((string)($msg['text'] ?? $msg['caption'] ?? ''));
        if ($text === '') {
            return $this->ok();
        }

        $meta = ['source' => 'telegram'];
        if (!empty($msg['from']) && is_array($msg['from'])) {
            $parts = [];
            if (!empty($msg['from']['first_name'])) {
                $parts[] = trim((string) $msg['from']['first_name']);
            }
            if (!empty($msg['from']['last_name'])) {
                $parts[] = trim((string) $msg['from']['last_name']);
            }
            $author_name = trim(implode(' ', $parts));
            if ($author_name === '' && !empty($msg['from']['username'])) {
                $author_name = '@'.trim((string) $msg['from']['username']);
            }
            if ($author_name !== '') {
                $meta['author_name'] = $author_name;
            }
        }

        try {
            // bot_token методу не нужен, он использует только chat_token
            shopTgconsultPlugin::sendToVisitor('', $chat_token, $text, $meta);
            waLog::log("[webhook] IN << token={$chat_token}: ".$text, 'tgconsult.log');
        } catch (Exception $e) {
            try {
                waLog::log('[webhook] ERROR: '.$e->getMessage(), 'tgconsult.log');
            } catch (Exception $e2) {}
        }

        return $this->ok();
    }

    private function ok()
    {
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(['ok' => true]);
    }
}
