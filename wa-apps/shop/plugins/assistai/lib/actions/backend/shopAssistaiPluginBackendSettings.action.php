<?php

//Блок Настройки страницы настроек
class shopAssistaiPluginBackendSettingsAction extends waViewAction
{
    public function execute()
    {
        $app_config = wa()->getConfig()->getAppConfig('shop');
        $path = $app_config->getAppPath('plugins/assistai/templates/settings/SettingsBlock.html');
        $this->setTemplate($path);


        $activeAssistants = waRequest::post('activeAssistants');
        $activeActions = waRequest::post('activeActions');
        $jsonSetting = waRequest::post('jsonSetting');

        // Декодируем JSON
        $settingApi = json_decode($jsonSetting, true);
        // Проверяем, успешно ли декодирован JSON
        if (json_last_error() !== JSON_ERROR_NONE) {
            $settingApi = [];  // Если ошибка, возвращаем пустой массив
        }


        if (!$activeAssistants || !$activeActions) {
            echo '';
            return;
        }

        //Вытаскиваем массив текущего ассистента
        //Находим массив текущего асистента
        $activeAssistantArr = [];
        if (!empty($settingApi['assistants']) && !empty($activeAssistants)) {
            foreach ($settingApi['assistants'] as $assistant) {
                if ($assistant['id'] == $activeAssistants) {
                    $activeAssistantArr = $assistant;
                    break;
                }
            }
        }


        //Специальные парамтеры для страницы.
        if ($activeActions == 'embedding') {

            $embeddingSettings = wa('shop')->getPlugin('assistai')->getSettings('embedding');
            $this->view->assign('embeddingSettings', ifempty($embeddingSettings[$activeAssistants], []));

        } elseif ($activeActions == 'rules') {

            //Получаем максимальную длину инструкции
            $maxLength = ifempty($settingApi['company']['tariff']['instruction_length'], 100 );
            $this->view->assign('maxLength', $maxLength);

            $api = new shopAssistaiPluginApi();
            $ruleSettings = $api->getInstructions($activeAssistants);

            //Если строка длиннее максимума, обрезать
            if (mb_strlen($ruleSettings['instructions'], 'UTF-8') > $maxLength) {
                // Обрезаем строку до $maxLength символов
                $ruleSettings['instructions'] = mb_substr($ruleSettings['instructions'], 0, $maxLength, 'UTF-8');
            }

            $this->view->assign('ruleSettings', $ruleSettings);

            //Расчёт текущийх символов
            $length  = mb_strlen(ifempty($ruleSettings['instructions'] , '') , 'UTF-8');
            $this->view->assign('length', $length);
        }


        //Общие параметры
        $this->view->assign('settingApi', $activeAssistantArr);
        $this->view->assign('activeAssistants', $activeAssistants);
        $this->view->assign('activeActions', $activeActions);


    }

}