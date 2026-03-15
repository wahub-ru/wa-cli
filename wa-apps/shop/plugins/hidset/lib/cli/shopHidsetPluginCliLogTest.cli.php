<?php
/*
 * @link https://warslab.ru/
 * @author waResearchLab
 * @Copyright (c) 2023 waResearchLab
 */

class shopHidsetPluginCliLogTestCli extends waCliController
{
    public function execute()
    {
        $html = <<<HTML
Данная команда в цикличном режиме использует большие объемы данных и помещает их в переменную с целью достичь максимума и вызвать php Fatal Error.
В процессе работы на экран и в лог-файл будет выводиться информация для демонстрации того, что процесс идет.
HTML;
        echo $html .  PHP_EOL;
        $model = new shopProductSkusModel();
        $data = $model->getAll();
        while (true) {
            $data = array_merge($data, $data);
            echo count($data) . PHP_EOL;
            waLog::log(count($data), 'cliLogTest.log');
        }
    }
}