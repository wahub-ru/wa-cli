<?php

class shopCallrequestPluginFrontendSendAction extends waJsonController
{
    public function execute()
    {
        // Получаем поля
        $name  = waRequest::post('name', '', waRequest::TYPE_STRING_TRIM);
        $phone = waRequest::post('phone', '', waRequest::TYPE_STRING_TRIM);
        $email = waRequest::post('email', '', waRequest::TYPE_STRING_TRIM);

        // Простейшая валидация
        if (!$name || !$phone) {
            $this->errors[] = 'Заполните обязательные поля';
            return;
        }

        // Сохраняем заявку (пример, можешь адаптировать под свою модель)
        $model = new shopCallrequestPluginRequestModel();
        $id = $model->insert([
            'create_datetime' => date('Y-m-d H:i:s'),
            'name'            => $name,
            'phone'           => $phone,
            'email'           => $email ?: null,
            'status'          => 'new',
        ]);

        // --- Отправка письма ---

        // 1) получаем email из настроек плагина
        $plugin   = wa('shop')->getPlugin('callrequest');
        $settings = $plugin->getSettings();
        $email_to = trim((string)($settings['email_to'] ?? ''));

        waLog::log(['email_to' => $email_to], 'callrequest_mail_debug.log');

        if ($email_to) {
            waLog::log('BEFORE SENDING MAIL', 'callrequest_mail_debug.log');

            try {
                // 2) Формируем тело письма
                $body = "<p>Новая заявка №{$id}</p>";
                $body .= "<p><strong>Имя:</strong> " . htmlspecialchars($name) . "</p>";
                $body .= "<p><strong>Телефон:</strong> " . htmlspecialchars($phone) . "</p>";
                if ($email) {
                    $body .= "<p><strong>Email:</strong> " . htmlspecialchars($email) . "</p>";
                }
                $body .= "<p>Дата: " . date('Y-m-d H:i:s') . "</p>";

                // 3) Создаём объект письма
                $mail = new waMailMessage(
                    'Новая заявка обратного звонка', // subject
                    $body                             // body (HTML)
                );

                // обязательно указать формат
                $mail->setContentType('text/html; charset=utf-8');

                // кому отправляем
                $mail->setTo($email_to);

                // от кого
                // waMail::getDefaultFrom() берёт правильный email из конфигурации
                $mail->setFrom(waMail::getDefaultFrom());

                // 4) Отправка
                $mail->send();
                waLog::log(['mail_send_result' => $result], 'callrequest_mail_debug.log');


            } catch (Exception $e) {
                // Логируем ошибку, чтобы понять, что не так
                waLog::log('MAIL EXCEPTION: '.$e->getMessage(), 'callrequest_mail_debug.log');
            }
        } else {
            waLog::log('NO EMAIL SPECIFIED', 'callrequest_mail_debug.log');
        }

        // Возвращаем результат
        $this->response = ['ok' => 1, 'id' => $id];
    }
}
