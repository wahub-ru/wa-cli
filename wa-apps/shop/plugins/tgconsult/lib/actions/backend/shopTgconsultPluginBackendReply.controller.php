<?php

class shopTgconsultPluginBackendReplyController extends waJsonController
{
    public function execute()
    {
        $chat_id = (int) waRequest::post('chat_id', 0, waRequest::TYPE_INT);
        $text    = trim((string) waRequest::post('text', '', waRequest::TYPE_STRING_TRIM));
        if (!$chat_id || $text === '') {
            $this->errors[] = 'Пустые данные';
            return;
        }

        if (!shopTgconsultPlugin::ensureSchema()) {
            $this->errors[] = 'Чат временно недоступен';
            return;
        }

        $db  = new waModel();
        $now = date('Y-m-d H:i:s');

        // чат + токен
        $chat = $db->query("SELECT * FROM shop_tgconsult_chat WHERE id = i:id LIMIT 1", ['id'=>$chat_id])->fetchAssoc();
        if (!$chat) {
            $this->errors[] = 'Чат не найден';
            return;
        }
        $token = (string) $chat['token'];

        $settings = shopTgconsultPlugin::pluginSettings();
        shopTgconsultPlugin::messageMetaColumnExists();
        $manager_name = shopTgconsultPlugin::defaultManagerName($settings);
        $author_name = $this->currentUserName($manager_name);
        $display_name = (shopTgconsultPlugin::managerNameMode($settings) === 'responder')
            ? $author_name
            : $manager_name;

        $meta = json_encode([
            'source' => 'backend',
            'author_id' => (int) wa()->getUser()->getId(),
            'author_name' => $author_name,
        ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

        // сохранить сообщение менеджера
        try {
            $db->exec(
                "INSERT INTO shop_tgconsult_message (chat_id, sender, text, meta, created)
                 VALUES (i:cid, 'manager', s:txt, s:meta, s:now)",
                ['cid' => $chat_id, 'txt' => $text, 'meta' => $meta, 'now' => $now]
            );
        } catch (Exception $e) {
            $db->exec(
                "INSERT INTO shop_tgconsult_message (chat_id, sender, text, created)
                 VALUES (i:cid, 'manager', s:txt, s:now)",
                ['cid' => $chat_id, 'txt' => $text, 'now' => $now]
            );
        }
        $msg_id = (int) $db->query('SELECT LAST_INSERT_ID()')->fetchField();

        // обновить updated
        $db->exec("UPDATE shop_tgconsult_chat SET updated = s:now WHERE id = i:id", ['now'=>$now,'id'=>$chat_id]);

        // отправить в TG (если настроено)
        try {
            $bot     = trim((string) ifset($settings, 'bot_token', ''));
            $chat_to = trim((string) ifset($settings, 'manager_chat_id', ''));
            if ($bot === '') {
                $bot = trim((string) ifset($settings, 'tg_token', ''));
            }
            if ($chat_to === '') {
                $chat_to = trim((string) ifset($settings, 'tg_chat_id', ifset($settings, 'chat_id', '')));
            }
            if ($bot && $chat_to) {
                $tag = '[[tgc:'.$token.':'.$msg_id.']]';
                $resp = shopTgconsultPlugin::tgApi($bot, 'sendMessage', [
                    'chat_id' => $chat_to,
                    'text'    => "Ответ — ".$display_name.":\n\n".$text."\n\n".$tag,
                ]);
                if (empty($resp['ok'])) {
                    waLog::log('[tgconsult] backend reply TG error response: '.json_encode($resp, JSON_UNESCAPED_UNICODE), 'tgconsult.log');
                }
            }
        } catch (Exception $e) {
            try { waLog::log('[tgconsult] backend reply TG error: '.$e->getMessage(), 'tgconsult.log'); } catch (Exception $e2) {}
        }

        $this->response = [
            'status'   => 'ok',
            'id'       => $msg_id,
            'created'  => date('d.m.Y H:i', strtotime($now)),
            'created_raw' => $now,
            'created_ts' => strtotime($now),
            'text'     => $text,
            'author_name' => $display_name,
        ];
    }

    private function currentUserName(string $fallback): string
    {
        $user = wa()->getUser();
        if (!$user) {
            return $fallback;
        }

        try {
            $formatted = trim((string) waContactNameField::formatName($user));
            if ($formatted !== '') {
                return $formatted;
            }
        } catch (Exception $e) {
        }

        try {
            $firstname = trim((string) $user->get('firstname'));
            $lastname = trim((string) $user->get('lastname'));
            $full = trim($firstname.' '.$lastname);
            if ($full !== '') {
                return $full;
            }
        } catch (Exception $e) {
        }

        try {
            $name = trim((string) $user->getName());
            if ($name !== '') {
                return $name;
            }
        } catch (Exception $e) {
        }

        try {
            $login = trim((string) $user->get('login'));
            if ($login !== '') {
                return $login;
            }
        } catch (Exception $e) {
        }

        return $fallback;
    }
}
