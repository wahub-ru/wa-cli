<?php

class shopTgconsultPluginBackendSaveSettingsController extends waJsonController
{
    public function execute()
    {
        if (!wa()->getUser()->isAdmin('shop')) {
            throw new waRightsException('Access denied');
        }

        $plugin_id = 'tgconsult';
        $namespace = wa()->getApp().'_'.$plugin_id;

        /** @var shopPlugin $plugin */
        $plugin    = wa('shop')->getPlugin($plugin_id);
        $old       = (array) $plugin->getSettings();
        $settings  = waRequest::post($namespace, [], waRequest::TYPE_ARRAY);
        if (!$settings) {
            $settings = waRequest::post($plugin_id, [], waRequest::TYPE_ARRAY);
        }
        if (!is_array($settings)) {
            $settings = [];
        }
        $raw_post = is_array($_POST) ? $_POST : [];
        $fallback_keys = [
            'enabled', 'bot_token', 'manager_chat_id', 'welcome', 'manager_name', 'manager_name_mode',
            'manager_photo', 'manager_photo_delete', 'hide_button', 'icon_color', 'widget_position',
            'widget_offset_side', 'widget_offset_bottom', 'position', 'offset_side', 'offset_bottom',
            'working_hours_enabled', 'working_timezone', 'offhours_autoreply',
            'work_mon_enabled', 'work_mon_start', 'work_mon_end',
            'work_tue_enabled', 'work_tue_start', 'work_tue_end',
            'work_wed_enabled', 'work_wed_start', 'work_wed_end',
            'work_thu_enabled', 'work_thu_start', 'work_thu_end',
            'work_fri_enabled', 'work_fri_start', 'work_fri_end',
            'work_sat_enabled', 'work_sat_start', 'work_sat_end',
            'work_sun_enabled', 'work_sun_start', 'work_sun_end',
            'webhook_url',
        ];
        foreach ($fallback_keys as $key) {
            if (!array_key_exists($key, $settings) && array_key_exists($key, $raw_post)) {
                $settings[$key] = $raw_post[$key];
            }
        }

        // чекбоксы → 0/1
        $settings['enabled']              = !empty($settings['enabled']) ? 1 : 0;
        $settings['manager_photo_delete'] = !empty($settings['manager_photo_delete']) ? 1 : 0;
        $settings['hide_button']          = !empty($settings['hide_button']) ? 1 : 0;
        $settings['working_hours_enabled']= !empty($settings['working_hours_enabled']) ? 1 : 0;

        $settings['manager_name_mode'] = (ifset($settings, 'manager_name_mode', 'settings') === 'responder')
            ? 'responder'
            : 'settings';
        $settings['widget_position'] = shopTgconsultPlugin::normalizeWidgetPosition(
            ifset($settings, 'widget_position', ifset($settings, 'position', 'right'))
        );

        $settings['widget_offset_side'] = $this->normalizeOffset(
            ifset($settings, 'widget_offset_side', ifset($settings, 'offset_side', 22)),
            22
        );
        $settings['widget_offset_bottom'] = $this->normalizeOffset(
            ifset($settings, 'widget_offset_bottom', ifset($settings, 'offset_bottom', 70)),
            70
        );
        $settings['position'] = $settings['widget_position'];
        $settings['offset_side'] = $settings['widget_offset_side'];
        $settings['offset_bottom'] = $settings['widget_offset_bottom'];
        $settings['working_timezone'] = $this->normalizeTimezone(ifset($settings, 'working_timezone', ''));
        $settings['offhours_autoreply'] = trim((string) ifset($settings, 'offhours_autoreply', ''));
        if ($settings['offhours_autoreply'] === '') {
            $settings['offhours_autoreply'] = 'Сейчас мы вне графика работы. Оставьте, пожалуйста, ваши контакты для связи, и мы ответим в рабочее время.';
        }

        $day_defaults = [
            'mon' => ['enabled' => 1, 'start' => '09:00', 'end' => '18:00'],
            'tue' => ['enabled' => 1, 'start' => '09:00', 'end' => '18:00'],
            'wed' => ['enabled' => 1, 'start' => '09:00', 'end' => '18:00'],
            'thu' => ['enabled' => 1, 'start' => '09:00', 'end' => '18:00'],
            'fri' => ['enabled' => 1, 'start' => '09:00', 'end' => '18:00'],
            'sat' => ['enabled' => 0, 'start' => '10:00', 'end' => '16:00'],
            'sun' => ['enabled' => 0, 'start' => '10:00', 'end' => '16:00'],
        ];
        foreach ($day_defaults as $day => $default) {
            $enabled_key = 'work_'.$day.'_enabled';
            $start_key = 'work_'.$day.'_start';
            $end_key = 'work_'.$day.'_end';

            $settings[$enabled_key] = !empty($settings[$enabled_key]) ? 1 : 0;
            $settings[$start_key] = $this->normalizeTime(ifset($settings, $start_key, $default['start']), $default['start']);
            $settings[$end_key] = $this->normalizeTime(ifset($settings, $end_key, $default['end']), $default['end']);
        }

        // 1) удаление фото: чистим настройку и удаляем локальный файл, если он из wa-data
        if (!empty($settings['manager_photo_delete'])) {
            $current = (string) ifset($old['manager_photo'], '');
            if ($current !== '') {
                // base URL того же пространства, что использует upload-контроллер
                $base_url  = wa()->getDataUrl('plugins/tgconsult/', true, 'shop', true); // абсолютный
                if (strpos($current, $base_url) === 0) {
                    $rel  = substr($current, strlen($base_url)); // manager/xxxx.ext
                    $path = wa()->getDataPath('plugins/tgconsult/'.$rel, true, 'shop'); // абсолютный путь
                    if (is_file($path)) { @unlink($path); }
                }
            }
            $settings['manager_photo']        = '';
            $settings['manager_photo_delete'] = 0; // не храним «флаг удаления» в настройках
        }

        // 2) сохраняем все поля как есть (в т.ч. icon_color, hide_button и т.д.)
        $plugin->saveSettings($settings);
        try {
            $asm = new waAppSettingsModel();
            $asm->set('shop', 'plugin.tgconsult.widget_position', (string) $settings['widget_position']);
            $asm->set('shop', 'plugin.tgconsult.widget_offset_side', (string) $settings['widget_offset_side']);
            $asm->set('shop', 'plugin.tgconsult.widget_offset_bottom', (string) $settings['widget_offset_bottom']);
            $asm->set('shop', 'plugin.tgconsult.position', (string) $settings['position']);
            $asm->set('shop', 'plugin.tgconsult.offset_side', (string) $settings['offset_side']);
            $asm->set('shop', 'plugin.tgconsult.offset_bottom', (string) $settings['offset_bottom']);
        } catch (Exception $e) {
        }

        $this->response = [
            'ok' => true,
            'saved' => [
                'widget_position' => $settings['widget_position'],
                'widget_offset_side' => $settings['widget_offset_side'],
                'widget_offset_bottom' => $settings['widget_offset_bottom'],
            ],
        ];
    }

    private function normalizeOffset($value, int $fallback): int
    {
        if ($value === '' || $value === null || !is_numeric($value)) {
            return $fallback;
        }
        $offset = (int) $value;
        if ($offset < 0) {
            return 0;
        }
        if ($offset > 500) {
            return 500;
        }
        return $offset;
    }

    private function normalizeTimezone($value): string
    {
        $tz = trim((string) $value);
        if ($tz !== '' && in_array($tz, timezone_identifiers_list(), true)) {
            return $tz;
        }
        return date_default_timezone_get() ?: 'UTC';
    }

    private function normalizeTime($value, string $fallback): string
    {
        $time = trim((string) $value);
        if (!preg_match('~^(\d{1,2}):(\d{2})$~', $time, $m)) {
            return $fallback;
        }
        $h = (int) $m[1];
        $i = (int) $m[2];
        if ($h < 0 || $h > 23 || $i < 0 || $i > 59) {
            return $fallback;
        }
        return sprintf('%02d:%02d', $h, $i);
    }
}
