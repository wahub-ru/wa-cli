<?php


// https://test.3na3.ru/webasyst/shop/?plugin=assistai&action=ajax
class shopAssistaiPluginBackendAjaxController extends waController
{

    public function execute()
    {
        $mode = waRequest::post('mode');
        //waLog::dump($mode, 'shop/plugins/assistai/erro777r.log');
        if (method_exists($this, $mode)) {
            // Вызов метода
            $result = $this->$mode();
            echo $result;
        }
    }


    //Удаление иконки чата
    private function removeIcon()
    {
        $type = waRequest::post('type');
        $activeAssistants = waRequest::post('activeAssistants');

        $embeddingSettings = wa('shop')->getPlugin('assistai')->getSettings('embedding');
        //Находим в настрйоках имя удалемого файла
        $fileName = ifempty($embeddingSettings[$activeAssistants][$type]);
        if (empty($fileName)) {
            return;
        }
        //Стираем значение в массиве
        $embeddingSettings[$activeAssistants][$type] = '';
        $path = wa()->getDataPath('plugins/assistai/icons', true, 'shop') . "/" . $fileName;
        waFiles::delete($path);
        //Перезаписываем сохранение
        wa('shop')->getPlugin('assistai')->saveSettings(['embedding' => json_encode($embeddingSettings)]);
    }


    //Сохранение настроек встраивания.
    private function saveEmbedding()
    {

        $post = waRequest::post();
        $activeAssistants = $post['activeAssistants'];
        $jsonSetting = $post['jsonSetting'];

        unset($post['activeAssistants'], $post['jsonSetting']);
        //Получаем данные и потом записываем их же т.к. может быть другие асистенты в этом же массиве
        $embeddingSettings = wa('shop')->getPlugin('assistai')->getSettings('embedding');
        if (empty($embeddingSettings[$activeAssistants])) {
            $embeddingSettings[$activeAssistants] = [];
        }

        $file = waRequest::file('iconImage');
        //Основая картинка
        $newName = '';
        if ($file->uploaded()) {
            $newName = 'iconImage-' . time() . "." . $file->extension;
            $path = wa()->getDataPath('plugins/assistai/icons', true, 'shop') . "/" . $newName;
            $file->copyTo($path);
        } elseif (!empty($embeddingSettings[$activeAssistants]['iconImage'])) {
            $newName = $embeddingSettings[$activeAssistants]['iconImage'];
        }
        $post['iconImage'] = $newName;


        //Ховер картинка
        $file = waRequest::file('hoverIconImage');
        $newName = '';
        if ($file->uploaded()) {
            $newName = 'hoverIconImage-' . time() . "." . $file->extension;
            $path = wa()->getDataPath('plugins/assistai/icons', true, 'shop') . "/" . $newName;
            $file->copyTo($path);
        } elseif (!empty($embeddingSettings[$activeAssistants]['hoverIconImage'])) {
            $newName = $embeddingSettings[$activeAssistants]['hoverIconImage'];
        }
        $post['hoverIconImage'] = $newName;
        unset($post['mode']);

        //Добавляем в настройки токен-код чата
        $tokenMessenger = '';
        $decodedSetting = json_decode($jsonSetting, true);

        //waLog::dump($decodedSetting, 'shop/create.log');

        if (!empty($decodedSetting['assistants'])) {
            foreach ($decodedSetting['assistants'] as $assistant) {
                if ($assistant['id'] == $activeAssistants) {
                    $tokenMessenger = $assistant['messengers'][0]['token'];
                }
            }
        }
        $post['tokenMessenger'] = $tokenMessenger;


        //Создаём отдельную характеристику хранящую витрину и токен чата только для включенных витрин.
        $showCases = wa('shop')->getPlugin('assistai')->getSettings('showCases');


        //Ищем если есть в массиве этот асистент то удаляем
        foreach ($showCases as $domain => $assistId) {
            if ($assistId == $activeAssistants) {
                unset($showCases[$domain]);
            }
        }
        //Если вскличено правило то создаём новое
        if ($post['enabled'] == 'on') {
            $showCases[$post['selectShowcase']] = $activeAssistants;
        }
        wa('shop')->getPlugin('assistai')->saveSettings(['showCases' => json_encode($showCases)]);

        $embeddingSettings[$activeAssistants] = $post;
        //waLog::dump($post, 'shop/create.log');
        wa('shop')->getPlugin('assistai')->saveSettings(['embedding' => json_encode($embeddingSettings)]);

    }

    private function saveRules()
    {
        $post = waRequest::post();
        $data = [
            'greeting' => $post['greeting'],
            'name' => $post['assistName'],
            'instructions' => $post['rules'],
            'assistId' => $post['activeAssistants'],
        ];
        $api = new shopAssistaiPluginApi();
        $api->saveInstructions($data);
    }


}