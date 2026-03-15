<?php


class shopAssistaiPluginApi
{
    private static $url = 'https://ai.upsale.site/api';
    private $token;

    public static function getUrl()
    {
        return self::$url;
    }

    public function __construct()
    {
        $this->token = wa('shop')->getPlugin('assistai')->getSettings('token');
    }

    //Получить общий массив параметров пользователя
    public function getSettings()
    {
        return $this->api(['mode' => 'getSettingsByApi']);
    }

    //Получить инструкцию конкретного асистента.
    public function getInstructions($assistId)
    {
        return $this->api(['mode' => 'getInstructionsByApi', 'assistId' => $assistId]);
    }


    //Сохранить Имя,Инструкцию и приветсвие
    public function saveInstructions($data)
    {
        $data = array_merge( ['mode' => 'saveInstructionsByApi'], $data);
        return $this->api($data);
    }


    //getSettingsByApi
    private function api($postData)
    {
        $url = self::getUrl();

        // Инициализация cURL
        $ch = curl_init();
        $postData['token'] = $this->token;
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

        if (waSystemConfig::isDebug()) {
            waLog::log($response, 'shop/plugins/assistai/responseApi.log');
        }

        $responseApi = json_decode($response, 1);

        return $responseApi;
    }

}