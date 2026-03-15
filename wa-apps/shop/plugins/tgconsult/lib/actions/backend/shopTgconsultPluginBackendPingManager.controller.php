<?php

class shopTgconsultPluginBackendPingManagerController extends waJsonController
{
    public function execute()
    {
        $p = wa('shop')->getPlugin('tgconsult');
        $token = trim((string)$p->getSettings('bot_token'));
        if ($token === '') { $this->response = ['ok'=>false,'error'=>'Нет токена']; return; }

        $resp = shopTgconsultPlugin::sendToManager($token, "✅ Тест от плагина tgconsult: ".date('Y-m-d H:i:s'));
        $this->response = ['ok' => (bool)ifset($resp,'ok',false), 'result' => $resp];
    }
}
