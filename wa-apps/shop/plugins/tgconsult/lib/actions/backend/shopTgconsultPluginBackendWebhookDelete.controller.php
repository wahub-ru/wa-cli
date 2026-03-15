<?php

class shopTgconsultPluginBackendWebhookDeleteController extends waJsonController
{
    public function execute()
    {
        $p = wa('shop')->getPlugin('tgconsult');
        $token = trim((string)$p->getSettings('bot_token'));
        if ($token === '') { $this->errors[] = 'Сначала сохраните токен бота.'; return; }

        $resp = shopTgconsultPlugin::tgApi($token, 'deleteWebhook');
        $this->response = ['ok' => (bool)ifset($resp,'ok',false), 'result' => $resp];
    }
}
