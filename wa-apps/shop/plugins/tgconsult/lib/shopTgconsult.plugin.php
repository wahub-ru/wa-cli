<?php

class shopTgconsultPlugin extends shopPlugin
{
    /** Telegram Bot API base */
    const API_BASE = 'https://api.telegram.org/bot';

    /** Фолбэк ID чата менеджера (если не задан в настройках) */
    const BOT_MANAGER_CHAT_ID = 123456789; // при желании уберите вовсе

    /**
     * Базовый URL витрины магазина (учитывает /shop/ и т.п.)
     * Например, вернёт "https://proffierb.ru/shop".
     */
    protected static function getFrontendBaseUrl(): string
    {
        return rtrim(wa()->getRouteUrl('shop/frontend', [], true), '/');
    }


    public static function ensureSchema(): bool
    {
        static $ready = null;

        if ($ready !== null) {
            return $ready;
        }

        $ready = self::requiredTablesExist();
        if ($ready) {
            return true;
        }

        $install_path = wa()->getAppPath('plugins/tgconsult/lib/config/install.php', 'shop');
        if (is_readable($install_path)) {
            include $install_path;
        }

        $ready = self::requiredTablesExist();
        return $ready;
    }

    private static function requiredTablesExist(): bool
    {
        return self::tableExists('shop_tgconsult_chat') && self::tableExists('shop_tgconsult_message');
    }

    private static function tableExists(string $table): bool
    {
        try {
            $db = new waModel();
            return (bool) $db->query("SHOW TABLES LIKE ?", $table)->fetch();
        } catch (Exception $e) {
            return false;
        }
    }

    /* ================= Меню в админке ================= */

    public function backendExtendedMenu(&$params)
    {
        self::ensureSchema();
        $url = wa()->getAppUrl('shop') . '?plugin=tgconsult&action=chats';
        $unread = self::unreadDialogsCount();

        // Абсолютный статик-URL папки плагина
        $icon_base = rtrim(wa()->getAppStaticUrl('shop/plugins/tgconsult'), '/');
        $icon_url  = $icon_base . '/img/plugin.png'; // ваш файл

        $params['menu']['tgconsult'] = [
            'name'         => 'Диалоги'.($unread > 0 ? ' ('.$unread.')' : ''),
            // Вставляем свою иконку 16x16
            'icon'         => '<img src="' . htmlspecialchars($icon_url, ENT_QUOTES, 'UTF-8') . '" width="16" height="16" alt="" style="vertical-align:-3px;">' . $this->backendNotifyMarkup(),
            'placement'    => 'body',
            'insert_after' => 'orders',
            'url'          => $url,
            'count'        => $unread,
        ];
    }

    public function backendMenu()
    {
        self::ensureSchema();
        $url = wa()->getAppUrl('shop') . '?plugin=tgconsult&action=chats';
        $unread = self::unreadDialogsCount();

        // Статический URL до папки плагина
        $icon_base = rtrim(wa()->getAppStaticUrl('shop'), '/').'/plugins/tgconsult';
        $icon_url  = rtrim(wa()->getAppStaticUrl('shop', true), '/').'/plugins/tgconsult/img/plugin.png';

        // (необязательно) кеш-бастер на основе mtime файла
        $fs_path = wa()->getConfig()->getRootPath().'/wa-apps/shop/plugins/tgconsult/img/plugin.png';
        if (is_readable($fs_path)) {
            $icon_url .= '?v=' . filemtime($fs_path);
        }

        $img = '<img src="' . htmlspecialchars($icon_url, ENT_QUOTES, 'UTF-8') . '" width="16" height="16" alt="" style="vertical-align:-3px;">';
        $badge = ($unread > 0)
            ? ' <span class="indicator red">'.(int) $unread.'</span>'
            : '';

        return [
            'core_li' => '<li id="tgconsult-oldui-menu" class="no-tab"><a href="'.$url.'">'.$img.' Диалоги'.$badge.'</a></li>' . $this->backendNotifyMarkup()
        ];
    }

    protected function backendNotifyMarkup()
    {
        $static_base = rtrim(wa()->getAppStaticUrl('shop', true), '/').'/plugins/tgconsult';
        $js_path = wa()->getAppPath('plugins/tgconsult/js/backend-notify.js', 'shop');
        $js_v = is_readable($js_path) ? (string) filemtime($js_path) : (string) $this->getVersion();

        $cfg = [
            'poll_url'  => wa()->getAppUrl('shop') . '?plugin=tgconsult&action=chats&json=1',
            'chats_url' => wa()->getAppUrl('shop') . '?plugin=tgconsult&action=chats',
        ];
        $json = str_replace('</', '<\/', json_encode($cfg, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));

        return '<script>window.TGCONSULT_ADMIN_NOTIFY=' . $json . ';</script>'
            . '<script defer src="' . $static_base . '/js/backend-notify.js?v=' . $js_v . '"></script>';
    }

    public function backendCustomer($customer)
    {
        if (!self::ensureSchema()) {
            return;
        }

        $contact_id = (int) ifset($customer, 'contact_id', ifset($customer, 'id', 0));
        if ($contact_id <= 0) {
            return;
        }

        $db = new waModel();
        $chat_count = (int) $db->query(
            "SELECT COUNT(*) FROM shop_tgconsult_chat WHERE customer_id = i:id",
            ['id' => $contact_id]
        )->fetchField();
        if ($chat_count <= 0) {
            return;
        }

        return [
            'action_link' => $this->backendCustomerHistoryActionLink($contact_id, $chat_count),
            'header' => $this->backendCustomerHistoryMarkup(),
        ];
    }

    protected function backendCustomerHistoryActionLink(int $contact_id, int $chat_count): string
    {
        $label = 'История сообщений';
        $legacy = false;
        try {
            $legacy = (wa()->whichUI() === '1.3');
        } catch (Exception $e) {
        }

        if ($legacy) {
            $suffix = ($chat_count > 1) ? ' <span class="hint">(' . (int) $chat_count . ')</span>' : '';
            return '<a href="javascript:void(0);" class="js-tgc-customer-history-link" data-customer-id="' . (int) $contact_id . '"><i class="icon16 notebook"></i>' . $label . $suffix . '</a>';
        }

        return '<a href="javascript:void(0);" class="button small light-gray rounded js-tgc-customer-history-link" data-customer-id="' . (int) $contact_id . '"><i class="fas fa-comments text-blue"></i> ' . $label . '</a>';
    }

    protected function backendCustomerHistoryMarkup(): string
    {
        static $once = false;
        if ($once) {
            return '';
        }
        $once = true;

        $static_base = rtrim(wa()->getAppStaticUrl('shop', true), '/').'/plugins/tgconsult';
        $js_path = wa()->getAppPath('plugins/tgconsult/js/customer-history.js', 'shop');
        $js_v = is_readable($js_path) ? (string) filemtime($js_path) : (string) $this->getVersion();

        $cfg = [
            'endpoint' => wa()->getAppUrl('shop') . '?plugin=tgconsult&action=customerhistory',
        ];
        $json = str_replace('</', '<\/', json_encode($cfg, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));

        return '<script>window.TGCONSULT_CUSTOMER_HISTORY=' . $json . ';</script>'
            . '<script defer src="' . $static_base . '/js/customer-history.js?v=' . $js_v . '"></script>';
    }

    /* ================= Виджет на витрине ================= */

    public function frontendAssets()
    {
        // 1) Не лезем в AJAX-ответы (фильтры, подгрузка слайдов и т.п.)
        if (waRequest::isXMLHttpRequest()) {
            return;
        }

        // 2) Не печатаем виджет на своих JSON-эндпоинтах
        $path = rtrim(parse_url((string) waRequest::server('REQUEST_URI'), PHP_URL_PATH), '/');
        if (preg_match('~/(tgconsult)(/|$)~', $path)) {
            return;
        }

        static $once = false;
        if ($once) {
            return;
        }
        $once = true;

        $s = $this->getSettings();
        if (empty($s['enabled'])) {
            return;
        }
        try {
            $asm = new waAppSettingsModel();
            $map = [
                'widget_position' => [
                    'plugin.tgconsult.widget_position',
                    'plugin.tgconsult.position',
                    'tgconsult.widget_position',
                    'tgconsult.position',
                ],
                'widget_offset_side' => [
                    'plugin.tgconsult.widget_offset_side',
                    'plugin.tgconsult.offset_side',
                    'tgconsult.widget_offset_side',
                    'tgconsult.offset_side',
                ],
                'widget_offset_bottom' => [
                    'plugin.tgconsult.widget_offset_bottom',
                    'plugin.tgconsult.offset_bottom',
                    'tgconsult.widget_offset_bottom',
                    'tgconsult.offset_bottom',
                ],
            ];
            foreach ($map as $target_key => $source_keys) {
                foreach ($source_keys as $source_key) {
                    $val = $asm->get('shop', $source_key, null);
                    if ($val !== null && $val !== '') {
                        $s[$target_key] = $val;
                        break;
                    }
                }
            }
        } catch (Exception $e) {
        }

        $static_base = rtrim(wa()->getAppStaticUrl('shop'), '/').'/plugins/tgconsult';
        // База витрины с учётом /shop/
        $front       = self::getFrontendBaseUrl();

        $position_raw = ifset($s, 'widget_position', ifset($s, 'position', 'right'));
        $position = self::normalizeWidgetPosition($position_raw);
        $offset_side = max(0, (int) ifset($s, 'widget_offset_side', ifset($s, 'offset_side', 22)));
        $offset_bottom = max(0, (int) ifset($s, 'widget_offset_bottom', ifset($s, 'offset_bottom', 70)));

        $cfg = [
            'welcome'      => (string) ifset($s, 'welcome', 'Здравствуйте! Чем помочь?'),
            'manager_name' => (string) ifset($s, 'manager_name', 'Менеджер'),
            'load_url'     => $front.'/tgconsult/load/',
            'send_url'     => $front.'/tgconsult/send/',
            'icon_color'   => (string) ((isset($s['icon_color']) && $s['icon_color'] !== '') ? $s['icon_color'] : '#0D6EFD'),
            'hide_button'  => !empty($s['hide_button']),
            'position'     => $position,
            'position_raw' => (string) $position_raw,
            'widget_position' => $position,
            'offset_side'  => $offset_side,
            'offset_bottom'=> $offset_bottom,
            'widget_offset_side' => $offset_side,
            'widget_offset_bottom' => $offset_bottom,
        ];
        if (!empty($s['manager_photo'])) {
            $cfg['manager_photo'] = (string) $s['manager_photo'];
        }

        $json = str_replace('</', '<\/', json_encode($cfg, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
        $v = (string) $this->getVersion();
        $css_path = wa()->getAppPath('plugins/tgconsult/css/widget.css', 'shop');
        $js_widget_path = wa()->getAppPath('plugins/tgconsult/js/widget.js', 'shop');
        $js_api_path = wa()->getAppPath('plugins/tgconsult/js/api.js', 'shop');
        $css_v = is_readable($css_path) ? (string) filemtime($css_path) : $v;
        $js_widget_v = is_readable($js_widget_path) ? (string) filemtime($js_widget_path) : $v;
        $js_api_v = is_readable($js_api_path) ? (string) filemtime($js_api_path) : $v;

        $html = [];
        $html[] = "\n<!-- tgconsult widget -->";

        if ($position === 'left') {
            $html[] = '<style>#tgconsult-root{left:var(--tgc-side,22px)!important;right:auto!important}#tgconsult-root .tgc-button,#tgconsult-root .tgc-window,#tgconsult-root .tgc-scroll-down{left:0!important;right:auto!important}</style>';
        } else {
            $html[] = '<style>#tgconsult-root{right:var(--tgc-side,22px)!important;left:auto!important}#tgconsult-root .tgc-button,#tgconsult-root .tgc-window,#tgconsult-root .tgc-scroll-down{right:0!important;left:auto!important}</style>';
        }

        if (!empty($s['hide_button'])) {
            $html[] = '<style>#tgconsult-root .tgc-button{display:none!important}</style>';
        }

        $html[] = '<link rel="stylesheet" href="'.$static_base.'/css/widget.css?v='.$css_v.'">';
        $html[] = '<script>window.TGCONSULT_CFG='.$json.';</script>';
        $html[] = '<script defer src="'.$static_base.'/js/widget.js?v='.$js_widget_v.'"></script>';
        $html[] = '<script defer src="'.$static_base.'/js/api.js?v='.$js_api_v.'"></script>';
        $html[] = '<!-- /tgconsult -->'."\n";

        return implode("\n", $html);
    }

    /* ================= Telegram helpers ================= */

    /** URL вебхука по умолчанию (absolute), учитывает /shop/ */
    public static function webhookUrl(): string
    {
        return self::getFrontendBaseUrl() . '/tgconsult/webhook/';
    }

    /** URL ping-эндпоинта (если используешь где-то для проверки) */
    public static function pingUrl(): string
    {
        return self::getFrontendBaseUrl() . '/tgconsult/ping/';
    }

    /** Универсальный вызов Telegram Bot API (должен быть public static для контроллеров) */
    public static function tgApi(string $token, string $method, array $params = [])
    {
        $url = self::API_BASE . $token . '/' . $method;
        $ch  = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_CONNECTTIMEOUT => 10,
            CURLOPT_TIMEOUT        => 20,
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => $params
        ]);
        $resp = curl_exec($ch);
        $err  = curl_error($ch);
        curl_close($ch);

        if ($resp === false) {
            return ['ok' => false, 'description' => 'curl error: '.$err];
        }
        $j = json_decode($resp, true);
        return is_array($j) ? $j : ['ok' => false, 'description' => 'bad json'];
    }

    /** Отправка сообщения менеджеру (в его чат/группу) */
    public static function sendToManager($bot_token, $text, $reply_to = null)
    {
        $url  = self::API_BASE . $bot_token . '/sendMessage';
        $data = [
            'chat_id' => self::managerChatId(),
            'text'    => $text,
            'parse_mode' => 'HTML',
            'disable_web_page_preview' => true,
        ];
        if ($reply_to) {
            $data['reply_to_message_id'] = (int) $reply_to;
        }
        return self::httpPost($url, $data);
    }

    /** Сохранить ответ менеджера в чат посетителя (на сайт) */
    public static function sendToVisitor($bot_token, $chat_token, $text, array $meta = [])
    {
        if (!self::ensureSchema()) {
            return;
        }

        self::messageMetaColumnExists();

        $cm = new shopTgconsultChatModel();
        $chat = $cm->getByField('token', $chat_token);
        if (!$chat) {
            return;
        }

        $db = new waModel();
        $now = date('Y-m-d H:i:s');
        $meta_json = $meta ? json_encode($meta, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) : null;

        try {
            if ($meta_json !== null) {
                $db->exec(
                    "INSERT INTO shop_tgconsult_message (chat_id, sender, text, meta, created)
                     VALUES (i:cid, 'manager', s:txt, s:meta, s:now)",
                    ['cid' => (int) $chat['id'], 'txt' => (string) $text, 'meta' => $meta_json, 'now' => $now]
                );
            } else {
                $db->exec(
                    "INSERT INTO shop_tgconsult_message (chat_id, sender, text, created)
                     VALUES (i:cid, 'manager', s:txt, s:now)",
                    ['cid' => (int) $chat['id'], 'txt' => (string) $text, 'now' => $now]
                );
            }
        } catch (Exception $e) {
            // Фолбэк для старых схем, если колонка meta отсутствует.
            $db->exec(
                "INSERT INTO shop_tgconsult_message (chat_id, sender, text, created)
                 VALUES (i:cid, 'manager', s:txt, s:now)",
                ['cid' => (int) $chat['id'], 'txt' => (string) $text, 'now' => $now]
            );
        }

        $cm->updateById($chat['id'], ['updated' => $now]);
    }

    public static function pluginSettings(): array
    {
        try {
            /** @var shopPlugin $plugin */
            $plugin = wa('shop')->getPlugin('tgconsult');
            return (array) $plugin->getSettings();
        } catch (Exception $e) {
            return [];
        }
    }

    public static function defaultManagerName(array $settings = null): string
    {
        if ($settings === null) {
            $settings = self::pluginSettings();
        }
        $name = trim((string) ifset($settings, 'manager_name', ''));
        return ($name !== '') ? $name : 'Менеджер';
    }

    public static function managerNameMode(array $settings = null): string
    {
        if ($settings === null) {
            $settings = self::pluginSettings();
        }
        return (ifset($settings, 'manager_name_mode', 'settings') === 'responder') ? 'responder' : 'settings';
    }

    public static function normalizeWidgetPosition($value): string
    {
        $raw = strtolower(trim((string) $value));
        if ($raw === '') {
            return 'right';
        }
        $left_values = ['left', 'l', '1', 'true', 'yes', 'on', '0'];
        if (in_array($raw, $left_values, true) || strpos($raw, 'left') !== false) {
            return 'left';
        }
        if ($raw === 'right' || $raw === 'r' || $raw === '2' || strpos($raw, 'right') !== false) {
            return 'right';
        }
        return 'right';
    }

    public static function decodeMessageMeta($meta): array
    {
        if (is_array($meta)) {
            return $meta;
        }
        if (!is_string($meta) || $meta === '') {
            return [];
        }
        $decoded = json_decode($meta, true);
        return is_array($decoded) ? $decoded : [];
    }

    public static function messageMetaColumnExists(): bool
    {
        static $checked = false;
        static $exists = false;
        static $create_attempted = false;

        if ($checked && $exists) {
            return true;
        }

        if (!self::ensureSchema()) {
            return false;
        }

        $db = new waModel();
        try {
            $exists = (bool) $db->query("SHOW COLUMNS FROM `shop_tgconsult_message` LIKE 'meta'")->fetch();
            $checked = true;
        } catch (Exception $e) {
            $exists = false;
            $checked = true;
        }

        if (!$exists && !$create_attempted) {
            $create_attempted = true;
            try {
                $db->exec("ALTER TABLE `shop_tgconsult_message` ADD COLUMN `meta` TEXT NULL AFTER `text`");
            } catch (Exception $e) {
            }
            try {
                $exists = (bool) $db->query("SHOW COLUMNS FROM `shop_tgconsult_message` LIKE 'meta'")->fetch();
                $checked = true;
            } catch (Exception $e) {
                $exists = false;
                $checked = true;
            }
        }

        return $exists;
    }

    public static function chatReadMarkerColumnExists(): bool
    {
        static $checked = false;
        static $exists = false;
        static $create_attempted = false;

        if ($checked && $exists) {
            return true;
        }

        if (!self::ensureSchema()) {
            return false;
        }

        $db = new waModel();
        try {
            $exists = (bool) $db->query("SHOW COLUMNS FROM `shop_tgconsult_chat` LIKE 'last_read_visitor_id'")->fetch();
            $checked = true;
        } catch (Exception $e) {
            $exists = false;
            $checked = true;
        }

        if (!$exists && !$create_attempted) {
            $create_attempted = true;
            try {
                $db->exec("ALTER TABLE `shop_tgconsult_chat` ADD COLUMN `last_read_visitor_id` INT UNSIGNED NOT NULL DEFAULT 0 AFTER `updated`");
            } catch (Exception $e) {
            }
            try {
                $exists = (bool) $db->query("SHOW COLUMNS FROM `shop_tgconsult_chat` LIKE 'last_read_visitor_id'")->fetch();
                $checked = true;
            } catch (Exception $e) {
                $exists = false;
                $checked = true;
            }
        }

        return $exists;
    }

    public static function unreadDialogsCount(): int
    {
        if (!self::ensureSchema()) {
            return 0;
        }

        $db = new waModel();
        $manager_condition = "mm.sender = 'manager'";
        if (self::messageMetaColumnExists()) {
            $manager_condition .= " AND (mm.meta IS NULL OR mm.meta NOT LIKE '%\"type\":\"offhours_autoreply\"%')";
        }
        $read_field = self::chatReadMarkerColumnExists() ? "c.last_read_visitor_id" : "0";

        try {
            $count = $db->query("
                SELECT COUNT(*)
                  FROM (
                    SELECT c.id,
                           (SELECT MAX(mv.id)
                              FROM shop_tgconsult_message mv
                             WHERE mv.chat_id = c.id
                               AND mv.sender = 'visitor') AS last_visitor_id,
                           (SELECT MAX(mm.id)
                              FROM shop_tgconsult_message mm
                             WHERE mm.chat_id = c.id
                               AND {$manager_condition}) AS last_manager_id,
                           {$read_field} AS last_read_visitor_id
                      FROM shop_tgconsult_chat c
                  ) x
                 WHERE IFNULL(x.last_visitor_id, 0) > GREATEST(IFNULL(x.last_manager_id, 0), IFNULL(x.last_read_visitor_id, 0))
            ")->fetchField();
            return (int) $count;
        } catch (Exception $e) {
            return 0;
        }
    }

    public static function resolveManagerAuthorName(array $message, array $settings = null): string
    {
        if ($settings === null) {
            $settings = self::pluginSettings();
        }
        $fallback_name = self::defaultManagerName($settings);
        if (self::managerNameMode($settings) !== 'responder') {
            return $fallback_name;
        }

        $meta = self::decodeMessageMeta(isset($message['meta']) ? $message['meta'] : null);
        $author_name = trim((string) ifset($meta, 'author_name', ''));
        return ($author_name !== '') ? $author_name : $fallback_name;
    }

    public static function isWithinWorkingHours(array $settings = null, $timestamp = null): bool
    {
        if ($settings === null) {
            $settings = self::pluginSettings();
        }
        if (empty($settings['working_hours_enabled'])) {
            return true;
        }

        $tz = self::workingTimezone($settings);
        $dt = new DateTime('now', $tz);
        if ($timestamp !== null) {
            $dt->setTimestamp((int) $timestamp);
        }

        $day_map = [
            1 => 'mon',
            2 => 'tue',
            3 => 'wed',
            4 => 'thu',
            5 => 'fri',
            6 => 'sat',
            7 => 'sun',
        ];
        $day_key = $day_map[(int) $dt->format('N')];
        if (empty($settings['work_'.$day_key.'_enabled'])) {
            return false;
        }

        $from = self::timeToMinutes((string) ifset($settings, 'work_'.$day_key.'_start', '09:00'));
        $to   = self::timeToMinutes((string) ifset($settings, 'work_'.$day_key.'_end', '18:00'));
        if ($from === null || $to === null) {
            return false;
        }

        $now = ((int) $dt->format('G')) * 60 + (int) $dt->format('i');
        if ($from === $to) {
            return true;
        }
        if ($from < $to) {
            return ($now >= $from && $now < $to);
        }
        return ($now >= $from || $now < $to);
    }

    public static function workingTimezone(array $settings = null): DateTimeZone
    {
        if ($settings === null) {
            $settings = self::pluginSettings();
        }
        $tz_id = trim((string) ifset($settings, 'working_timezone', ''));
        if ($tz_id === '' || !in_array($tz_id, timezone_identifiers_list(), true)) {
            $tz_id = date_default_timezone_get() ?: 'UTC';
        }

        try {
            return new DateTimeZone($tz_id);
        } catch (Exception $e) {
            return new DateTimeZone('UTC');
        }
    }

    private static function timeToMinutes(string $time)
    {
        if (!preg_match('~^(\d{1,2}):(\d{2})$~', trim($time), $m)) {
            return null;
        }
        $h = (int) $m[1];
        $i = (int) $m[2];
        if ($h < 0 || $h > 23 || $i < 0 || $i > 59) {
            return null;
        }
        return ($h * 60) + $i;
    }

    /** Получить chat_id менеджера из настроек (или фолбэк) */
    private static function managerChatId()
    {
        try {
            /** @var shopPlugin $p */
            $p = wa('shop')->getPlugin('tgconsult');
            $id = trim((string)$p->getSettings('manager_chat_id'));
            if ($id !== '') return $id;
        } catch (Exception $e) {}
        return (string) self::BOT_MANAGER_CHAT_ID;
    }

    /** Простой POST-запрос */
    private static function httpPost($url, array $data)
    {
        $c = curl_init($url);
        curl_setopt_array($c, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => $data,
            CURLOPT_CONNECTTIMEOUT => 10,
            CURLOPT_TIMEOUT        => 20,
        ]);
        $resp = curl_exec($c);
        curl_close($c);
        return $resp ? json_decode($resp, true) : null;
    }
}
