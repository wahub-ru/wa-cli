<?php

class shopTgconsultPluginBackendChatsAction extends waViewAction
{
    public function execute()
    {
        $rows = $this->loadChats();
        $this->decorateChats($rows);
        $this->sortChats($rows);

        if (waRequest::get('json', 0, waRequest::TYPE_INT)) {
            $out = [];
            foreach ($rows as $row) {
                $out[] = [
                    'id' => (int) $row['id'],
                    'display_name' => (string) $row['display_name'],
                    'last_text' => (string) ifset($row, 'last_text', ''),
                    'last_created' => (string) ifset($row, 'last_created_fmt', ''),
                    'last_created_ts' => (int) ifset($row, 'last_created_ts', 0),
                    'notify_text' => (string) ifset($row, 'notify_text', ifset($row, 'last_text', '')),
                    'notify_created_ts' => (int) ifset($row, 'notify_created_ts', ifset($row, 'last_created_ts', 0)),
                    'notify_message_id' => (int) ifset($row, 'notify_message_id', ifset($row, 'last_visitor_id', 0)),
                    'unread' => !empty($row['unread']),
                ];
            }
            $this->renderJson(['status' => 'ok', 'chats' => $out]);
            return;
        }

        // гарантируем загрузку бэкенд-ресурсов (jQuery, стили админки)
        $this->setLayout(new shopBackendLayout());
        $this->view->assign('chats', $rows);

        $tpl = wa()->getAppPath('plugins/tgconsult/templates/actions/backend/Chats.html', 'shop');
        try { waLog::log('[tgconsult] Using Chats template: '.$tpl.' exists='.(file_exists($tpl)?'Y':'N'), 'tgconsult.log'); } catch (Exception $e) {}
        $this->setTemplate($tpl);
    }

    private function loadChats(): array
    {
        if (!shopTgconsultPlugin::ensureSchema()) {
            return [];
        }

        $db = new waModel();
        $manager_condition = "mm.sender = 'manager'";
        if (shopTgconsultPlugin::messageMetaColumnExists()) {
            $manager_condition .= " AND (mm.meta IS NULL OR mm.meta NOT LIKE '%\"type\":\"offhours_autoreply\"%')";
        }
        $read_field = shopTgconsultPlugin::chatReadMarkerColumnExists() ? "c.last_read_visitor_id" : "0";

        return $db->query("
            SELECT c.id, c.customer_id, c.token, c.updated,
                   (SELECT text FROM shop_tgconsult_message m WHERE m.chat_id = c.id ORDER BY id DESC LIMIT 1) AS last_text,
                   (SELECT created FROM shop_tgconsult_message m WHERE m.chat_id = c.id ORDER BY id DESC LIMIT 1) AS last_created,
                   (SELECT MAX(mv.id) FROM shop_tgconsult_message mv WHERE mv.chat_id = c.id AND mv.sender = 'visitor') AS last_visitor_id,
                   (SELECT text FROM shop_tgconsult_message mv WHERE mv.chat_id = c.id AND mv.sender = 'visitor' ORDER BY id DESC LIMIT 1) AS last_visitor_text,
                   (SELECT created FROM shop_tgconsult_message mv WHERE mv.chat_id = c.id AND mv.sender = 'visitor' ORDER BY id DESC LIMIT 1) AS last_visitor_created,
                   (SELECT MAX(mm.id) FROM shop_tgconsult_message mm WHERE mm.chat_id = c.id AND {$manager_condition}) AS last_manager_reply_id,
                   {$read_field} AS last_read_visitor_id
              FROM shop_tgconsult_chat c
          ORDER BY c.updated DESC
             LIMIT 200
        ")->fetchAll();
    }

    private function decorateChats(array &$rows): void
    {
        // имена контактов по customer_id (если есть)
        $names = [];
        $ids = [];
        foreach ($rows as $r) {
            if (!empty($r['customer_id'])) {
                $ids[] = (int) $r['customer_id'];
            }
        }
        $ids = array_values(array_unique(array_filter($ids)));
        if ($ids) {
            try {
                $coll = new waContactsCollection('id/'.implode(',', $ids));
                $contacts = $coll->getContacts('id,name,firstname,lastname', 0, count($ids));
                foreach ($contacts as $c) {
                    $names[(int) $c['id']] = waContactNameField::formatName($c);
                }
            } catch (Exception $e) {
            }
        }

        foreach ($rows as &$r) {
            $r['display_name'] = !empty($r['customer_id']) && !empty($names[(int) $r['customer_id']])
                ? $names[(int) $r['customer_id']]
                : 'Гость';
            if (empty($r['last_created'])) {
                $r['last_created'] = $r['updated'];
            }
            $r['last_created_fmt'] = $r['last_created']
                ? date('d.m.Y H:i', strtotime((string) $r['last_created']))
                : '';
            $r['last_created_ts'] = $r['last_created']
                ? (int) strtotime((string) $r['last_created'])
                : 0;
            $r['notify_text'] = (string) ifset($r, 'last_visitor_text', ifset($r, 'last_text', ''));
            $r['notify_created_ts'] = !empty($r['last_visitor_created'])
                ? (int) strtotime((string) $r['last_visitor_created'])
                : (int) ifset($r, 'last_created_ts', 0);
            $r['notify_message_id'] = (int) ifset($r, 'last_visitor_id', 0);
            $last_visitor_id = (int) ifset($r, 'last_visitor_id', 0);
            $last_manager_reply_id = (int) ifset($r, 'last_manager_reply_id', 0);
            $last_read_visitor_id = (int) ifset($r, 'last_read_visitor_id', 0);
            $r['unread'] = ($last_visitor_id > 0 && $last_visitor_id > max($last_manager_reply_id, $last_read_visitor_id));
        }
        unset($r);
    }

    private function sortChats(array &$rows): void
    {
        usort($rows, function (array $a, array $b): int {
            $a_unread = !empty($a['unread']) ? 1 : 0;
            $b_unread = !empty($b['unread']) ? 1 : 0;
            if ($a_unread !== $b_unread) {
                return ($b_unread <=> $a_unread);
            }

            $a_ts = (int) ifset($a, 'last_created_ts', 0);
            $b_ts = (int) ifset($b, 'last_created_ts', 0);
            if ($a_ts !== $b_ts) {
                return ($b_ts <=> $a_ts);
            }

            return ((int) ifset($b, 'id', 0)) <=> ((int) ifset($a, 'id', 0));
        });
    }

    private function renderJson(array $data): void
    {
        $this->getResponse()->addHeader('Content-Type', 'application/json; charset=utf-8');
        echo json_encode($data, JSON_UNESCAPED_UNICODE);
        exit;
    }
}
