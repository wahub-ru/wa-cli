<?php

class shopTgconsultPluginFrontendLoadController extends waController
{
    public function execute()
    {
        $after_id   = (int) waRequest::get('after_id', 0, waRequest::TYPE_INT);
        $chat_token = trim((string) waRequest::get('chat_token', '', waRequest::TYPE_STRING_TRIM));

        if (!shopTgconsultPlugin::ensureSchema()) {
            return $this->json([
                'ok'       => true,
                'messages' => [],
                'last_id'  => 0,
                'widget'   => $this->widgetRuntimeConfig(),
            ]);
        }

        $db = new waModel();

        // контекст
        $user = wa()->getUser();
        $customer_id = ($user && $user->isAuth()) ? (int) $user->getId() : 0;

        // стабильный sid для гостей
        $session_id = $this->getOrMakeVisitorSid();

        // === ИЩЕМ ЧАТ (НЕ СОЗДАЁМ!) ===
        $chat = null;

        if ($customer_id > 0) {
            // один чат на авторизованного — берём последний активный
            $chat = $db->query(
                "SELECT * FROM shop_tgconsult_chat WHERE customer_id = i:cid ORDER BY updated DESC LIMIT 1",
                ['cid' => $customer_id]
            )->fetchAssoc();

        } else {
            // ГОСТЬ: используем ТОЛЬКО наш sid (chat_token от клиента игнорируем — защита от утечек)
            $chat = $db->query(
                "SELECT * FROM shop_tgconsult_chat WHERE session_id = s:sid ORDER BY updated DESC LIMIT 1",
                ['sid' => $session_id]
            )->fetchAssoc();
        }

        // если чата нет — пустой ответ БЕЗ chat_token
        if (!$chat) {
            return $this->json([
                'ok'       => true,
                'messages' => [],
                'last_id'  => 0,
                'widget'   => $this->widgetRuntimeConfig(),
            ]);
        }

        // сообщения этой нити
        $fields = shopTgconsultPlugin::messageMetaColumnExists()
            ? "id, sender, text, meta, created"
            : "id, sender, text, NULL AS meta, created";

        $rows = $db->query(
            "SELECT {$fields}
               FROM shop_tgconsult_message
              WHERE chat_id = i:cid AND id > i:aid
           ORDER BY id ASC",
            ['cid' => (int) $chat['id'], 'aid' => $after_id]
        )->fetchAll();

        $plugin_settings = shopTgconsultPlugin::pluginSettings();
        foreach ($rows as &$row) {
            if ($row['sender'] === 'manager') {
                $row['author_name'] = shopTgconsultPlugin::resolveManagerAuthorName($row, $plugin_settings);
            } else {
                $row['author_name'] = 'Вы';
            }
            unset($row['meta']);
        }
        unset($row);

        $last_id = $after_id;
        foreach ($rows as $r) { $last_id = max($last_id, (int)$r['id']); }

        return $this->json([
            'ok'         => true,
            'chat_token' => (string) $chat['token'], // токен отдаём только для уже существующего чата
            'messages'   => $rows,
            'last_id'    => $last_id,
            'widget'     => $this->widgetRuntimeConfig(),
        ]);
    }

    private function widgetRuntimeConfig(): array
    {
        $settings = shopTgconsultPlugin::pluginSettings();
        $position_raw = ifset($settings, 'widget_position', ifset($settings, 'position', 'right'));
        return [
            'position_raw' => (string) $position_raw,
            'position' => shopTgconsultPlugin::normalizeWidgetPosition($position_raw),
            'offset_side' => max(0, (int) ifset($settings, 'widget_offset_side', ifset($settings, 'offset_side', 22))),
            'offset_bottom' => max(0, (int) ifset($settings, 'widget_offset_bottom', ifset($settings, 'offset_bottom', 70))),
        ];
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

    private function json(array $data, $code = 200)
    {
        http_response_code($code);
        header('Content-Type: application/json; charset=utf-8');
        header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
        echo json_encode($data, JSON_UNESCAPED_UNICODE);
        exit;
    }
}
