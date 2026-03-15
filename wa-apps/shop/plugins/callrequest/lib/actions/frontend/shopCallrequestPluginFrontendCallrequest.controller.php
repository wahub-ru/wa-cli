<?php

class shopCallrequestPluginFrontendCallrequestController extends waJsonController
{
    protected $disableCsrf = false;

    public function execute()
    {
        // Проверочный пинг: <префикс_витрины>/callrequest/?ping=1
        if (waRequest::get('ping', 0, waRequest::TYPE_INT)) {
            $this->response = array('ok' => 1, 'endpoint' => 'callrequest/', 'method' => waRequest::method());
            return;
        }

        if (waRequest::method() !== 'post') {
            $this->errors = array('message' => 'Метод не поддерживается. Используйте POST.');
            return;
        }

        $name   = trim(waRequest::post('name', '', waRequest::TYPE_STRING_TRIM));
        $phone  = trim(waRequest::post('phone', '', waRequest::TYPE_STRING_TRIM));
        $email  = trim(waRequest::post('email', '', waRequest::TYPE_STRING_TRIM));
        $policy = waRequest::post('policy', 0, waRequest::TYPE_INT);
        $fields = waRequest::post('fields', array(), waRequest::TYPE_ARRAY_TRIM);

        if (!$email && is_array($fields) && !empty($fields['email'])) {
            $email = trim((string)$fields['email']);
        }
        if ($name === '' || $phone === '') {
            $this->errors = array('message' => 'Заполните обязательные поля.');
            return;
        }

        $m = new shopCallrequestPluginRequestModel();
        $row = array(
            'create_datetime' => date('Y-m-d H:i:s'),
            'name'            => $name,
            'phone'           => $phone,
            'email'           => $email ?: null,
            'policy'          => $policy ? 1 : 0,
            'fields_json'     => json_encode(is_array($fields) ? $fields : array(), JSON_UNESCAPED_UNICODE),
            'ip'              => waRequest::getIp(),
            'user_agent'      => substr((string)waRequest::getUserAgent(), 0, 255),
            'referer'         => substr((string)waRequest::server('HTTP_REFERER'), 0, 1024),
            'status'          => 'new'
        );
        $id = $m->insert($row);

        // Уведомление на почту (если указано в настройках)
        try {
            $settings = wa('shop')->getPlugin('callrequest')->getSettings();
            $email_to = trim((string)($settings['email_to'] ?? ''));
            waLog::log(['email_to' => $email_to], 'callrequest_mail_debug.log');

            if ($email_to !== '') {
                $body  = "Новая заявка #{$id}\n";
                $body .= "Имя: {$name}\n";
                $body .= "Телефон: {$phone}\n";
                if ($email) { $body .= "Email: {$email}\n"; }
                if (!empty($fields)) {
                    $body .= "Доп. поля:\n";
                    foreach ($fields as $k => $v) {
                        $body .= " - {$k}: {$v}\n";
                    }
                }
                $body .= "Время: {$row['create_datetime']}\n";
                $body .= "Источник: {$row['referer']}\n";

                $mm = new waMailMessage('Заявка на обратный звонок', nl2br($body));
                $mm->setTo($email_to);
                $mm->setFrom(waMail::getDefaultFrom());
                $mm->send();
            }
        } catch (Exception $e) {}

        $this->response = array('ok' => 1, 'id' => $id);
    }
}
