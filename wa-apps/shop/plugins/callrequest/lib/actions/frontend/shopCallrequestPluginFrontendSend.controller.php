<?php

class shopCallrequestPluginFrontendSendController extends waJsonController
{
    public function execute()
    {
        $name  = (string) waRequest::post('name', '', waRequest::TYPE_STRING_TRIM);
        $phone = (string) waRequest::post('phone', '', waRequest::TYPE_STRING_TRIM);
        $from  = (string) waRequest::post('from_url', '', waRequest::TYPE_STRING_TRIM);

        // квиз-данные (fields[...])
        $fields = waRequest::post('fields', array(), waRequest::TYPE_ARRAY);
        if (!is_array($fields)) {
            $fields = array();
        }

        // настройки (совместимость с двумя префиксами)
        $m   = new waAppSettingsModel();
        $app = 'shop';
        $get = function($k, $def = null) use ($m, $app) {
            $v = $m->get($app, 'plugins.callrequest.'.$k, null);
            if ($v === null) { $v = $m->get($app, 'plugin.callrequest.'.$k, $def); }
            return $v;
        };

        if (!(int) $get('enabled', 1)) {
            $this->errors[] = 'Плагин отключён';
            return;
        }

        if ($get('policy_enabled', 0)) {
            if (!waRequest::post('policy')) {
                $this->errors[] = 'Нужно согласиться с политикой';
                return;
            }
        }

        if ($name === '' || $phone === '') {
            $this->errors[] = 'Заполните обязательные поля';
            return;
        }

        $referer = $from ?: (string) waRequest::server('HTTP_REFERER');
        $ip = waRequest::getIp();
        $ua = (string) waRequest::server('HTTP_USER_AGENT');

        // fields_json: сохраняем как JSON
        $fields_json = '';
        try {
            // чистим null/пустые
            foreach ($fields as $k => $v) {
                if ($v === null) unset($fields[$k]);
            }
            $fields_json = json_encode($fields, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        } catch (Exception $e) {
            $fields_json = '';
        }

        // ---- Сохранение в БД (правильные колонки) ----
        try {
            if (class_exists('shopCallrequestPluginRequestModel')) {
                $rm = new shopCallrequestPluginRequestModel();

                // Вставляем ТОЛЬКО те колонки, которые реально есть в таблице
                $row = array(
                    'create_datetime' => date('Y-m-d H:i:s'),
                    'name'            => $name,
                    'phone'           => $phone,
                    'referer'         => $referer,
                    'status'          => 'new',
                    'ip'              => $ip,
                    'user_agent'      => $ua,
                    'fields_json'     => $fields_json,
                );

                if ($row) {
                    $rm->insert($row);
                }
            }
        } catch (Exception $e) {
            waLog::log('[callrequest] insert failed: '.$e->getMessage(), 'callrequest.log');
        }

        // ---- E-mail ----
        $email_to = (string) $get('email_to', '');
        if ($email_to) {
            try {

                // Человеко-понятная дата (RU)
                $dt = new DateTime();
                $formatter = new IntlDateFormatter(
                    'ru_RU',
                    IntlDateFormatter::LONG,
                    IntlDateFormatter::SHORT,
                    null,
                    null,
                    "d MMMM yyyy 'г. в' HH:mm"
                );
                $human_date = $formatter->format($dt);

                $lines = [];

                // Заголовок письма
                $lines[] = "<b>Заявка на расчет пакетов</b>";
                $lines[] = "<br>";

                // Основные данные (значения — жирным)
                $lines[] = "Имя: <b>".htmlspecialchars($name, ENT_QUOTES, 'UTF-8')."</b>";
                $lines[] = "Телефон: <b>".htmlspecialchars($phone, ENT_QUOTES, 'UTF-8')."</b>";
                $lines[] = "Страница: <b>".htmlspecialchars($referer, ENT_QUOTES, 'UTF-8')."</b>";
                $lines[] = "Дата: <b>{$human_date}</b>";

                // Квиз / доп. поля
                if (!empty($fields)) {
                    $lines[] = "<br><b>Данные заказа:</b>";

                    foreach ($fields as $k => $v) {
                        $k = htmlspecialchars($k, ENT_QUOTES, 'UTF-8');
                        $v = htmlspecialchars($v, ENT_QUOTES, 'UTF-8');
                        $lines[] = "{$k}: <b>{$v}</b>";
                    }
                }

                // HTML-тело
                $body = implode("<br>", $lines);

                $msg = new waMailMessage(
                    "$name запрашивает расчет пакетов - {$human_date}",
                    $body
                );
                $msg->setTo($email_to);
                $msg->setContentType('text/html; charset=utf-8');
                $msg->send();

            } catch (Exception $e) {
                waLog::log('[callrequest] mail failed: '.$e->getMessage(), 'callrequest.log');
            }
        }


        $this->response = array('ok' => 1);
    }
}