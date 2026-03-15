<?php
/*
 * @link https://warslab.ru/
 * @author waResearchLab
 * @Copyright (c) 2021 waResearchLab
 */

class shopHidsetPluginCli extends waCliController
{
    public $task_map;
    protected $handRun = false;
    protected $taskParams = [];

    public function __construct()
    {
        if (wa()->getEnv() === 'cli' && !waLicensing::check('shop/plugins/hidset')->isPremium()) {
            shopHidsetPlugin::setLog(shopHidsetPlugin::ERROR_NEED_PREMIUM, null, false);
            throw new waException(shopHidsetPlugin::ERROR_NEED_PREMIUM);
        }
    }

    public function preExecute()
    {
        $task_files = glob(wa()->getAppPath('plugins/hidset/lib/cli/tasks/shopHidsetPlugin*.task.php'));
        foreach ($task_files as $tfile) {
            $afile = explode('/', $tfile);
            $file = array_pop($afile);
            $file = str_replace('.task.php', 'Task', $file);
            if (class_exists($file)) {
                try {
                    $task = new $file();
                    $this->task_map[$task->getCommand()] = [
                        'task' => $task,
                        'command' => $task->getCommand(),
                        'description' => $task->getDescription(),
                        'addon' => (strpos($file, 'shopHidsetPluginAddon') === 0),
                        'handRun' => $task->handRun,
                        'formData' => $task->getFormData(),
                        'params' => $task->taskParams
                    ];
                } catch (Exception $e) {
                    shopHidsetPlugin::setLog($e->getMessage(), $file);
                }
            }
        }
        $this->task_map = shopHidsetPlugin::sortArray($this->task_map, 'command', 'asc', true);
    }

    public function execute()
    {
        $task = waRequest::param('task');
        if (!isset($task, $this->task_map)) {
            shopHidsetPlugin::setLog(sprintf(shopHidsetPlugin::ERROR_UNKNOWN_TASK, $task));
            return;
        }
        $timeStart = time();
        try {
            $this->task_map[$task]['task']->run();
        } catch (Exception $e) {
            shopHidsetPlugin::setLog(sprintf(shopHidsetPlugin::ERROR_CLI_TASK_RUN, $task, $e->getMessage()));
            return;
        }
        shopHidsetPlugin::setLog(sprintf(shopHidsetPlugin::CLI_RUN_DONE, $task, time() - $timeStart));
    }

    public function getTasksInfo()
    {
        $info = [];
        $apps = wa()->getApps();
        $isCloud = isset($apps['cloud']);
        foreach ($this->task_map as $tcommand => $task) {
            $info[] = [
                'command' => $isCloud ? '-task ' . $tcommand : $tcommand,
                'description' => $task['description'],
                'addon' => $task['addon'],
                'handRun' => $task['handRun'],
                'formData' => $task['formData'],
                'params' => $task['params']
            ];
        }
        return $info;
    }

    public function getFormData()
    {
        return false;
    }

    protected function returnError($message) {
        if (wa()->getEnv() === 'cli') {
            echo $message;
        } else {
            throw new waException($message);
        }
    }

}

class shopHidsetPluginRepair extends shopRepairActions
{
    public function __construct()
    {
    }
}