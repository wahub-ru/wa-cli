<?php

//Процессинг регистрации по API

//https://test.3na3.ru/webasyst/shop/?plugin=assistai&action=registration
class shopAssistaiPluginBackendRegistrationController extends waController
{
    /* Внутренние коды
        code:
        1- есть ошибки (запрос не делаем)
        2- не получен код ответа.
        3- неожиданный код возврата из внешенй системы
        Внешние коды
        50 - хеш и эмейл уже есть в базе. (пользователь корректен и существует)
        51 - email уже есть в базе
        52 - хеш уже есть в базе
        53 - ничего не найдено. Выслан одноразовый код.

        54 - Пользователь не найден по логину/паролю
        55 - выдан токен

        56 - однорзовый код не подошел
        57 - выдан токен после новой регистрации
     */
    private $responseData = [
        'code' => '',
        'error' => '',
        'hint' => '',
        'data' => [],
    ];

    private function response()
    {
        echo json_encode($this->responseData);
        exit();
    }


    private function setResponse($mode, $val, $complete = false)
    {
        $this->responseData[$mode] = $val;
        if ($complete) {
            $this->response();
        }
    }


    public function execute()
    {
        $mode = waRequest::post('mode');
        //Записываем email в настройки
        $email = waRequest::post('email');

        if (!empty($email)) {
            wa('shop')->getPlugin('assistai')->saveSettings(['email' => $email]);
        }

        //waLog::dump($mode, 'shop/plugins/assistai/erro777r.log');
        if (method_exists($this, $mode)) {
            // Вызов метода
            $result = $this->$mode();
            echo $result;
        }
    }


    public function tokenByOneTimeCode()
    {

        //Получаем код установки
        $identityHash = $this->getIdentityHash();
        $mode = waRequest::post('mode');
        $email = $this->getEmail();
        $oneTimeCode = waRequest::post("oneTimeCode");

        $data = [
            'oneTimeCode' => $oneTimeCode,
            'email' => $email,
            'mode' => $mode,
            'identityHash' => $identityHash,
        ];

        //отправка одноразового кода
        $responseApi = $this->send($data);

        switch ($responseApi['code']) {
            case 56:
                $this->setResponse('error', '<strong>Неверный одноразовый код!</strong> Убедитесь, что вы используете тот же Email, на который был отправлен код. Проверьте правильность ввода кода и повторите попытку. Если с момента отправки прошло более 15 минут, запросите новый одноразовый код.<br><span class="enter-password-return">вернутся к запросу кода</span>', true);
                break;
            case 57:
                //Токен успешно получен
                if (!empty($responseApi['token'])) {
                    wa('shop')->getPlugin('assistai')->saveSettings(['token' => $responseApi['token']]);
                    //email есть в базе
                    $email = wa('shop')->getPlugin('assistai')->getSettings('email');
                    $this->setResponse('data', ['token' => $responseApi['token'], 'email' => $email, 'password' => $responseApi['password']]);
                } else {
                    //Если токен не пришел
                    $this->setResponse('code', 1);
                    $this->setResponse('error', '<strong>Ошибка!</strong> Не удалось записать токен, пожалуйста свяжитесь с разработчиком плагина по email: <a href="mailto:zakaz@upsale.site">zakaz@upsale.site</a>');
                }

                //Записываем успешно подошедший пароль
                wa('shop')->getPlugin('assistai')->saveSettings(['password' => $responseApi['password']]);
                //Возвращаем результат
                $this->response();
                break;
        }
    }


    //Если отправляется пароль. (привязка по паролю)
    public function tokenByLogin()
    {
        //Получаем код установки
        $identityHash = $this->getIdentityHash();
        $mode = waRequest::post('mode');
        $email = $this->getEmail();
        $password = waRequest::post("password");

        $data = [
            'hash' => hash('sha256', $password),
            'email' => $email,
            'mode' => $mode,
            'identityHash' => $identityHash,
        ];

        $responseApi = $this->send($data);
        switch ($responseApi['code']) {
            case 54:
                $this->setResponse('error', '<strong>Не верный логин или пароль!</strong> введите корректный логин или пароль<br><span class="enter-password-return">вернутся в меню регистрации</span>', true);
                break;
            case 55:
                //Токен успешно получен
                if (!empty($responseApi['token'])) {
                    wa('shop')->getPlugin('assistai')->saveSettings(['token' => $responseApi['token']]);
                    //email есть в базе
                    $email = wa('shop')->getPlugin('assistai')->getSettings('email');
                    $this->setResponse('data', ['token' => $responseApi['token'], 'email' => $email]);
                } else {
                    //Если токен не пришел
                    $this->setResponse('code', 1);
                    $this->setResponse('error', '<strong>Ошибка!</strong> Не удалось записать токен, пожалуйста свяжитесь с разработчиком плагина по email: <a href="mailto:zakaz@upsale.site">zakaz@upsale.site</a>');
                }



                //Записываем успешно подошедший пароль
                wa('shop')->getPlugin('assistai')->saveSettings(['password' => $password]);
                //Возвращаем результат
                $this->response();
                break;
        }
    }


    public function getIdentityHash()
    {
        //Получаем код установки
        $path = waConfig::get('wa_path_config') . '/config.php';
        $config = include($path);
        $identityHash = md5(ifempty($config['identity_hash'], ''));
        if (empty($identityHash)) {
            waLog::dump('Не удалось получить код установки', 'shop/plugins/assistai/error.log');
            $this->setResponse('code', 1);
            $this->setResponse('error', '<strong>Ошибка!</strong> Не удалось получить код установки, пожалуйста свяжитесь с разработчиком плагина по email: <a href="mailto:zakaz@upsale.site">zakaz@upsale.site</a>', true);
        }

        return $identityHash;
    }


    public function getEmail()
    {
        $email = waRequest::post('email');
        if (empty($email)) {
            waLog::dump('Не удалось получить email', 'shop/plugins/assistai/error.log');
            $this->setResponse('code', 1);
            $this->setResponse('error', '<strong>Ошибка!</strong> Не удалось получить email, пожалуйста свяжитесь с разработчиком плагина по email: <a href="mailto:zakaz@upsale.site">zakaz@upsale.site</a>', true);
        }
        return $email;
    }

    //Получение временного кода.
    public function getTemporaryCode()
    {
        //Получаем код установки
        $identityHash = $this->getIdentityHash();
        $mode = waRequest::post('mode');
        $email = $this->getEmail();

        $data = ['email' => $email, 'mode' => $mode, 'identityHash' => $identityHash];

        //Отправка запроса к API
        $responseApi = $this->send($data);

        //Записываем внешний код в ответ.
        $this->setResponse('code', $responseApi['code']);
        switch ($responseApi['code']) {
            case 50:
                $this->setResponse('hint', '<strong>Необходимо ввести пароль!</strong> Ваша установка Shop-Script уже была связана с AssistAi. Для восстановления интеграции введите пароль доступа в личном кабинете AssistAi.', true);
                break;
            case 51:
                //email есть в базе
                $this->setResponse('hint', '<strong>Необходимо ввести пароль!</strong> Ваш Email уже зарегистрирован в системе AssistAi. Чтобы связать вашу установку Shop-Script с AssistAi, введите пароль доступа в личном кабинете AssistAi.', true);
                break;
            case 52:
                //хэш есть в базе
                $this->setResponse('data', ['email' => $responseApi['email']]);
                $this->setResponse('hint', "<strong>Введите учётные данные!</strong> Ваша установка Shop-Script уже связана с AssistAi с использованием Email {$responseApi['email']}. Пожалуйста, войдите в систему, используя этот Email и пароль доступа в личном кабинете AssistAi.", true);
                break;
            case 53:
                $this->setResponse('hint', "<strong>Введите код из email!</strong> На ваш Email отправлен одноразовый пароль, действующий в течение 15 минут. Введите его в поле выше, чтобы завершить регистрацию.", true);
                break;
        }

        $this->setResponse('code', 3);
        $this->setResponse('error', '<strong>Ошибка!</strong> Неожиданный код возврата, пожалуйста свяжитесь с разработчиком плагина по email: <a href="mailto:zakaz@upsale.site">zakaz@upsale.site</a>', true);
    }

    public function send($postData)
    {
        $url = shopAssistaiPluginApi::getUrl();

        // Инициализация cURL
        $ch = curl_init();
        //$token = wa('shop')->getPlugin('assistai')->getSettings('token');
        //$postData['token'] = $token;
        // Настройка cURL
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($postData));
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/x-www-form-urlencoded'
        ]);
        // Выполнение запроса
        $response = curl_exec($ch);
        // Проверка на ошибки
        if (curl_errno($ch)) {
            waLog::dump('Ошибка cURL: ' . curl_error($ch), 'shop/plugins/assistai/error.log');
            //throw new Exception('Ошибка cURL: ' . curl_error($ch));
        }
        // Закрытие cURL
        curl_close($ch);
        // Возврат ответа


        $responseApi = json_decode($response, 1);
        if (waSystemConfig::isDebug()) {
            waLog::dump($responseApi, 'shop/plugins/assistai/responseApi.log');
        }
        if (empty($responseApi['code'])) {
            $this->setResponse('code', 2);
            $this->setResponse('error', '<strong>Ошибка!</strong> Внешний код ответа не получен, пожалуйста свяжитесь с разработчиком плагина по email: <a href="mailto:zakaz@upsale.site">zakaz@upsale.site</a>', true);
        } else {
            $this->setResponse('code', $responseApi['code']);
        }

        return $responseApi;
    }


}