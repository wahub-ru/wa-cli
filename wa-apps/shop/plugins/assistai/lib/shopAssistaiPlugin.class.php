<?php

class shopAssistaiPlugin extends shopPlugin
{


    public function frontendHead()
    {
        if (!empty($this->getAssistId())) {
            $this->addCss('css/frontend.css');
            $this->addJs('js/frontend.js');
        }
    }

    //Проверка/возврат идентификатора асистента
    public function getAssistId()
    {
        //Получаем настройки активных чатов
        $showCases = wa('shop')->getPlugin('assistai')->getSettings('showCases');
        if (empty($showCases)) {
            return;
        }
        //Если есть ключ all то выводим этого асистента.
        //Если нет то ищем какого выводить.
        $currentAssistant = '';
        if (!empty($showCases['all'])) {
            $currentAssistant = $showCases['all'];
        } else {
            //Определяем текущую витрину
            $firstSegment = wa()->getRouting()->getDomain(null, false, true);
            $secondSegment = wa()->getRouting()->getRootUrl();
            $secondSegment = rtrim($secondSegment, '/');
            if (empty($secondSegment)) {
                $currentShowcase = $firstSegment;
            } else {
                $currentShowcase = $firstSegment . '/' . $secondSegment;
            }
            if (!empty($showCases[$currentShowcase])) {
                $currentAssistant = $showCases[$currentShowcase];
            }
        }
        return $currentAssistant;
    }


    public function frontendHeader()
    {
        //Получаем идентификатор асистента для витрины
        $currentAssistant = $this->getAssistId();

        //Если не нашли подходящего асcистента - выходим
        if (empty($currentAssistant)) {
            return;
        }

        //Получаем настроки встраивания
        $embeddingSettings = wa('shop')->getPlugin('assistai')->getSettings('embedding');
        if (empty($embeddingSettings[$currentAssistant])) {
            waLog::dump('Ошибка: не найдены настройки', 'shop/assistai/error.log');
            return;
        }

        $embeddingSettings = $embeddingSettings[$currentAssistant];
        if ($embeddingSettings['enabled'] !== 'on') {
            return;
        }

        $action = new shopAssistaiPluginFrontendMessengerAction(['settings' => $embeddingSettings]);
        $page_html = $action->display(false);

        return $page_html;
        //waLog::dump($embeddingSettings, 'shop/create.log');
    }


    public static function getSettlements()
    {
        $settlements = array();
        $routing = wa()->getRouting();
        $domain_routes = $routing->getByApp('shop');
        foreach ($domain_routes as $domain => $routes) {
            $settlements['0']['title'] = 'На всех';
            $settlements['0']['value'] = 'all';
            foreach ($routes as $key => $route) {
                $s = $domain . '/' . $route['url'];
                $s = rtrim($s, '/*');
                $settlements[$s]['title'] = $s;
                $settlements[$s]['value'] = $s;
            }
        }
        return $settlements;
    }

}
