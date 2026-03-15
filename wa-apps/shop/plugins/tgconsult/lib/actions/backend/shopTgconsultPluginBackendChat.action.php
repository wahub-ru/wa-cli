<?php

class shopTgconsultPluginBackendChatAction extends waViewAction
{
public function execute()
{
    $this->setLayout(new shopBackendLayout());

    $chat_id = (int) waRequest::request('id', 0, waRequest::TYPE_INT);
    $after_id = (int) waRequest::request('after_id', 0, waRequest::TYPE_INT);
    if (!$chat_id) {
        $this->renderJson(['status'=>'fail','error'=>'chat_id required']); return;
    }

    if (!shopTgconsultPlugin::ensureSchema()) {
        $this->renderJson(['status'=>'fail','error'=>'Чат временно недоступен']); return;
    }

    $db = new waModel();
    $chat = $db->query("SELECT * FROM shop_tgconsult_chat WHERE id = i:id LIMIT 1", ['id'=>$chat_id])->fetchAssoc();
    if (!$chat) {
        $this->renderJson(['status'=>'fail','error'=>'chat not found']); return;
    }

    // Имя посетителя и ссылка в админку (если авторизован)
    $customer_name = 'Гость';
    $customer_url  = null;
    $cid = (int) ifset($chat['customer_id'], 0);
    if ($cid > 0) {
        try {
            $c = new waContact($cid);
            $customer_name = waContactNameField::formatName($c);
            // ссылка на карточку покупателя в текущем backend shop
            $customer_url = wa()->getAppUrl('shop') . '?action=customers#/id/' . $cid;
        } catch (Exception $e) {}
    }

    $plugin_settings = shopTgconsultPlugin::pluginSettings();
    $manager_name = shopTgconsultPlugin::defaultManagerName($plugin_settings);
    $manager_name_mode = shopTgconsultPlugin::managerNameMode($plugin_settings);

    $fields = shopTgconsultPlugin::messageMetaColumnExists()
        ? "id, sender, text, meta, created"
        : "id, sender, text, NULL AS meta, created";

    $messages = $db->query("
        SELECT {$fields}
          FROM shop_tgconsult_message
         WHERE chat_id = i:cid
           AND id > i:aid
      ORDER BY id ASC
    ", ['cid'=>$chat_id, 'aid'=>$after_id])->fetchAll();

    foreach ($messages as &$message) {
        if ($message['sender'] === 'manager') {
            $message['author_name'] = shopTgconsultPlugin::resolveManagerAuthorName($message, $plugin_settings);
        } else {
            $message['author_name'] = $customer_name;
        }
        unset($message['meta']);
    }
    unset($message);

    if (waRequest::get('json', 0, waRequest::TYPE_INT)) {
        $this->renderJson([
            'status'   => 'ok',
            'chat'     => [
                'id'            => (int)$chat['id'],
                'token'         => (string)$chat['token'],
                'title'         => (string)$chat['title'],
                'customer_id'   => (int)$chat['customer_id'],
                'customer_name' => $customer_name,
                'customer_url'  => $customer_url,
                'manager_name'  => $manager_name,
                'manager_name_mode' => $manager_name_mode,
                'updated'       => (string)$chat['updated'],
            ],
            'messages' => $messages
        ]);
        return; // renderJson делает exit, но оставим на всякий
    }

    // HTML (если кто-то всё же откроет диалог отдельно)
    $this->view->assign('chat', $chat);
    $this->view->assign('messages', $messages);
    $tpl = wa()->getAppPath('plugins/tgconsult/templates/actions/backend/Chat.html', 'shop');
    $this->setTemplate($tpl);
}


private function renderJson(array $data)
{
    $this->getResponse()->addHeader('Content-Type', 'application/json; charset=utf-8');
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit; 
}

}
