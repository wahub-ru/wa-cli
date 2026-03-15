<?php

class shopTgconsultPluginFrontendSendController extends waController
{
    public function execute()
    {
        $text       = trim((string) waRequest::post('text', '', waRequest::TYPE_STRING_TRIM));
        $chat_token = trim((string) waRequest::post('chat_token', '', waRequest::TYPE_STRING_TRIM));
        $hp         = trim((string) waRequest::post('website', '', waRequest::TYPE_STRING_TRIM)); // honeypot

        if ($hp !== '') return $this->json(['status'=>'ok']);
        if ($text === '') return $this->json(['status'=>'fail','error'=>'Пустое сообщение'], 400);
        if (!shopTgconsultPlugin::ensureSchema()) return $this->json(['status'=>'fail','error'=>'Чат временно недоступен'], 503);

        $db  = new waModel();
        $now = date('Y-m-d H:i:s');

        // контекст
        $user = wa()->getUser();
        $customer_id = ($user && $user->isAuth()) ? (int) $user->getId() : 0;
        $session_id  = $this->getOrMakeVisitorSid();

        // === НАХОДИМ/СОЗДАЁМ ЧАТ (только тут!) ===
        $chat = null;

        if ($customer_id > 0) {
            $chat = $db->query(
                "SELECT * FROM shop_tgconsult_chat WHERE customer_id = i:cid ORDER BY updated DESC LIMIT 1",
                ['cid'=>$customer_id]
            )->fetchAssoc();

            if (!$chat) {
                $chat_token = substr(sha1(uniqid('tgc', true)), 0, 32);
                $db->exec(
                    "INSERT INTO shop_tgconsult_chat (customer_id, session_id, token, title, created, updated)
                     VALUES (i:cid, NULL, s:tok, '', s:now, s:now)",
                    ['cid'=>$customer_id,'tok'=>$chat_token,'now'=>$now]
                );
                $chat = $db->query("SELECT * FROM shop_tgconsult_chat WHERE token = s:tok LIMIT 1", ['tok'=>$chat_token])->fetchAssoc();
            }
        } else {
            // ГОСТЬ: ищем чат по нашей sid (chat_token от клиента игнорируем)
            $chat = $db->query(
                "SELECT * FROM shop_tgconsult_chat WHERE session_id = s:sid ORDER BY updated DESC LIMIT 1",
                ['sid'=>$session_id]
            )->fetchAssoc();

            if (!$chat) {
                $chat_token = substr(sha1(uniqid('tgc', true)), 0, 32);
                $db->exec(
                    "INSERT INTO shop_tgconsult_chat (customer_id, session_id, token, title, created, updated)
                     VALUES (0, s:sid, s:tok, '', s:now, s:now)",
                    ['sid'=>$session_id,'tok'=>$chat_token,'now'=>$now]
                );
                $chat = $db->query("SELECT * FROM shop_tgconsult_chat WHERE token = s:tok LIMIT 1", ['tok'=>$chat_token])->fetchAssoc();
            }
        }

        if (!$chat) return $this->json(['status'=>'fail','error'=>'Не удалось создать/найти чат'], 500);

        $chat_id    = (int) $chat['id'];
        $chat_token = (string) $chat['token'];

        // Сохраняем сообщение
        $db->exec(
            "INSERT INTO shop_tgconsult_message (chat_id, sender, text, created)
             VALUES (i:cid, 'visitor', s:txt, s:now)",
            ['cid'=>$chat_id,'txt'=>$text,'now'=>$now]
        );
        $msg_id = (int) $db->query('SELECT LAST_INSERT_ID()')->fetchField();

        // Обновим updated
        $db->exec("UPDATE shop_tgconsult_chat SET updated = s:now WHERE id = i:id", ['now'=>$now,'id'=>$chat_id]);

        // Автоответ вне рабочего времени (таймзона и график берутся из настроек плагина)
        $plugin_settings = shopTgconsultPlugin::pluginSettings();
        $outside_working_hours = !shopTgconsultPlugin::isWithinWorkingHours($plugin_settings);
        if ($outside_working_hours) {
            $this->sendOffhoursAutoreply($db, $chat_id, $plugin_settings);
        }

        // Имя автора для Telegram
        $author = 'Гость';
        if ($customer_id > 0) {
            try {
                $c = new waContact($customer_id);
                $author = waContactNameField::formatName($c) ?: $c->getName() ?: ('Пользователь #'.$customer_id);
            } catch (Exception $e) {}
        }
        
        // URL страницы, с которой пишет покупатель
        $page_url = trim((string) waRequest::server('HTTP_REFERER', '', waRequest::TYPE_STRING_TRIM));
        if ($page_url !== '') {
            // убираем якорь, чтобы не засорять ссылку
            $hash_pos = strpos($page_url, '#');
            if ($hash_pos !== false) {
                $page_url = substr($page_url, 0, $hash_pos);
            }
        }


        // Отправляем в Telegram
        try {
            list($bot, $chat_to) = $this->getTgBotAndChat();
            if ($bot && $chat_to) {
$tag = '[[tgc:'.$chat_token.':'.$msg_id.']]';

$prefix = $outside_working_hours ? "Сообщение вне графика работы.\n\n" : '';
$text_to_send = $prefix."Новое сообщение — ".$author.":\n\n"
    .$text
    .($page_url ? "\n\nСтраница: ".$page_url : '')
    ."\n\n"
    .$tag;

$this->tgSendReliable($bot, $chat_to, $text_to_send);

            } else {
                waLog::log('[tgconsult] TG settings missing (bot_token or manager_chat_id)', 'tgconsult.log');
            }
        } catch (Exception $e) {
            waLog::log('[tgconsult] TG send exception: '.$e->getMessage(), 'tgconsult.log');
        }

        return $this->json(['status'=>'ok','id'=>$msg_id,'chat_token'=>$chat_token]);
    }

    private function getOrMakeVisitorSid()
    {
        $sid = (string) waRequest::cookie('tgc_sid', '', waRequest::TYPE_STRING_TRIM);
        if ($sid === '') {
            $sid = substr(sha1(uniqid('tgc', true).mt_rand()), 0, 32);
            $expire = time() + 3600*24*365;
            try { wa()->getResponse()->setCookie('tgc_sid', $sid, $expire, '/'); }
            catch (Exception $e) { @setcookie('tgc_sid', $sid, $expire, '/'); }
        }
        return $sid;
    }

    private function getTgBotAndChat()
    {
        $bot = ''; $chat = '';
        try {
            /** @var shopTgconsultPlugin $plugin */
            $plugin = wa()->getPlugin('tgconsult');
            if ($plugin) {
                $ps = (array) $plugin->getSettings();
                if (!empty($ps['bot_token']))       $bot  = (string) $ps['bot_token'];
                if (!empty($ps['manager_chat_id'])) $chat = (string) $ps['manager_chat_id'];
                if (!$bot)  $bot  = (string) ($ps['tg_token'] ?? $ps['token'] ?? '');
                if (!$chat) $chat = (string) ($ps['tg_chat_id'] ?? $ps['chat_id'] ?? $ps['tg_chat'] ?? '');
            }
        } catch (Exception $e) {}
        if ($bot === '' || $chat === '') {
            try {
                $asm = new waAppSettingsModel();
                if ($bot === '') {
                    $bot = (string) ($asm->get('shop','plugin.tgconsult.bot_token')
                             ?: $asm->get('shop','plugin.tgconsult.tg_token')
                             ?: $asm->get('shop','plugin.tgconsult.token')
                             ?: $asm->get('shop','tgconsult.bot_token')
                             ?: $asm->get('shop','tgconsult.tg_token')
                             ?: $asm->get('shop','tgconsult.token'));
                }
                if ($chat === '') {
                    $chat = (string) ($asm->get('shop','plugin.tgconsult.manager_chat_id')
                              ?: $asm->get('shop','plugin.tgconsult.tg_chat_id')
                              ?: $asm->get('shop','plugin.tgconsult.chat_id')
                              ?: $asm->get('shop','tgconsult.manager_chat_id')
                              ?: $asm->get('shop','tgconsult.tg_chat_id')
                              ?: $asm->get('shop','tgconsult.chat_id')
                              ?: $asm->get('shop','plugin.tgconsult.tg_chat'));
                }
            } catch (Exception $e) {}
        }
        return [$bot, $chat];
    }

    private function tgSendReliable($bot, $chat_id, $text)
    {
        $payload = ['chat_id'=>$chat_id, 'text'=>$text];
        $url = 'https://api.telegram.org/bot'.$bot.'/sendMessage';

        if (function_exists('curl_init')) {
            $ch = curl_init($url);
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($payload, '', '&'));
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 5);
            curl_setopt($ch, CURLOPT_TIMEOUT, 12);
            $resp = curl_exec($ch);
            $ok = false;
            if ($resp !== false) { $j = json_decode($resp, true); $ok = is_array($j) && !empty($j['ok']); }
            curl_close($ch);
            if ($ok) return true;
        }

        if (ini_get('allow_url_fopen')) {
            $ctx = stream_context_create(['http'=>[
                'method'=>'POST',
                'header'=>"Content-Type: application/x-www-form-urlencoded\r\n",
                'content'=>http_build_query($payload, '', '&'),
                'timeout'=>12
            ]]);
            $resp = @file_get_contents($url, false, $ctx);
            if ($resp !== false) { $j = json_decode($resp, true); if (is_array($j) && !empty($j['ok'])) return true; }
        }

        try {
            $net = new waNet(['format'=>waNet::FORMAT_JSON, 'timeout'=>12]);
            $res = $net->query($url, $payload, waNet::METHOD_POST);
            if (!empty($res['ok'])) return true;
        } catch (Exception $e) {}
        return false;
    }

    private function sendOffhoursAutoreply(waModel $db, int $chat_id, array $settings)
    {
        shopTgconsultPlugin::messageMetaColumnExists();

        $text = trim((string) ifset($settings, 'offhours_autoreply', ''));
        if ($text === '') {
            $text = 'Сейчас мы вне графика работы. Оставьте, пожалуйста, ваши контакты для связи, и мы ответим в рабочее время.';
        }
        if ($this->hasOffhoursAutoReply($db, $chat_id, $text)) {
            return;
        }

        $meta = [
            'type' => 'offhours_autoreply',
            'source' => 'system',
            'author_name' => shopTgconsultPlugin::defaultManagerName($settings),
        ];
        $meta_json = json_encode($meta, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        $now = date('Y-m-d H:i:s');

        try {
            $db->exec(
                "INSERT INTO shop_tgconsult_message (chat_id, sender, text, meta, created)
                 VALUES (i:cid, 'manager', s:txt, s:meta, s:now)",
                ['cid' => $chat_id, 'txt' => $text, 'meta' => $meta_json, 'now' => $now]
            );
        } catch (Exception $e) {
            $db->exec(
                "INSERT INTO shop_tgconsult_message (chat_id, sender, text, created)
                 VALUES (i:cid, 'manager', s:txt, s:now)",
                ['cid' => $chat_id, 'txt' => $text, 'now' => $now]
            );
        }

        $db->exec(
            "UPDATE shop_tgconsult_chat SET updated = s:now WHERE id = i:id",
            ['now' => $now, 'id' => $chat_id]
        );
    }

    private function hasOffhoursAutoReply(waModel $db, int $chat_id, string $fallback_text): bool
    {
        try {
            if (shopTgconsultPlugin::messageMetaColumnExists()) {
                $id = $db->query(
                    "SELECT id
                       FROM shop_tgconsult_message
                      WHERE chat_id = i:cid
                        AND sender = 'manager'
                        AND meta LIKE s:meta
                      ORDER BY id DESC
                      LIMIT 1",
                    ['cid' => $chat_id, 'meta' => '%"type":"offhours_autoreply"%']
                )->fetchField();
            } else {
                $id = $db->query(
                    "SELECT id
                       FROM shop_tgconsult_message
                      WHERE chat_id = i:cid
                        AND sender = 'manager'
                        AND text = s:txt
                      ORDER BY id DESC
                      LIMIT 1",
                    ['cid' => $chat_id, 'txt' => $fallback_text]
                )->fetchField();
            }
            return !empty($id);
        } catch (Exception $e) {
            return false;
        }
    }

    private function json(array $data, $code = 200)
    {
        http_response_code($code);
        header('Content-Type: application/json; charset=utf-8');
        header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
        echo json_encode($data, JSON_UNESCAPED_UNICODE);
        exit;
    }
}
