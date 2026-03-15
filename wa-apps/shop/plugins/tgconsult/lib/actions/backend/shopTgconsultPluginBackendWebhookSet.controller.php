<?php

class shopTgconsultPluginBackendWebhookSetController extends waJsonController
{
    public function execute()
    {
        $p = wa('shop')->getPlugin('tgconsult');
        $token = trim((string)$p->getSettings('bot_token'));
        if ($token === '') { $this->errors[] = 'Сначала сохраните токен бота.'; return; }

        $url = trim((string)waRequest::post('url', ''));
        if ($url === '') { $url = shopTgconsultPlugin::webhookUrl(); }

        $resp = shopTgconsultPlugin::tgApi($token, 'setWebhook', [
            'url' => $url,
            'allowed_updates' => json_encode(['message','edited_message']),
        ]);
        $this->response = ['ok' => (bool)ifset($resp,'ok',false), 'result' => $resp];
    }
}
