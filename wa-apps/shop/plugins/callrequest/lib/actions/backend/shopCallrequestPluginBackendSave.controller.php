<?php

class shopToolsPluginBackendSaveController extends waController
{
    public function execute()
    {
        if (waRequest::method() !== 'post') {
            $this->json(array('ok' => 0, 'error' => 'Method not allowed'));
            return;
        }

        // значения из формы
        $enabled        = waRequest::post('enabled', 0, waRequest::TYPE_INT) ? 1 : 0;
        $trigger_class  = (string) waRequest::post('trigger_class', 'callrequest-open', waRequest::TYPE_STRING_TRIM);

        // пишем в оба префикса (совместимость)
        $m   = new waAppSettingsModel();
        $app = 'shop';
        foreach (array('plugins.callrequest.', 'plugin.callrequest.') as $pfx) {
            $m->set($app, $pfx.'enabled',        $enabled);
            $m->set($app, $pfx.'trigger_class',  $trigger_class);
        }

        $this->json(array('ok' => 1));
    }

    private function json($arr)
    {
        wa()->getResponse()->addHeader('X-Content-Type-Options', 'nosniff');
        wa()->getResponse()->addHeader('Content-Type', 'application/json; charset=utf-8');
        echo json_encode($arr);
    }
}
