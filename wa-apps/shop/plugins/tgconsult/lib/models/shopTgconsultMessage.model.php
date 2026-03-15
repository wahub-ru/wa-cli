<?php
class shopTgconsultMessageModel extends waModel
{
    protected $table = 'shop_tgconsult_message';

    public function add($chat_id, $sender, $text, array $meta = [])
    {
        $data = [
            'chat_id' => (int) $chat_id,
            'sender'  => (string) $sender,
            'text'    => (string) $text,
            'created' => date('Y-m-d H:i:s'),
        ];
        if ($meta && shopTgconsultPlugin::messageMetaColumnExists()) {
            $data['meta'] = json_encode($meta, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        }

        return $this->insert($data);
    }
}
