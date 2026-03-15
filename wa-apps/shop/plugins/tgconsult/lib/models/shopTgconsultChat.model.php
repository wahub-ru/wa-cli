<?php
class shopTgconsultChatModel extends waModel
{
    protected $table = 'shop_tgconsult_chat';

    public function getOrCreate(array $ctx): array
    {
        $customer_id = (int)ifset($ctx, 'customer_id', 0);
        $session_id  = (string)ifset($ctx, 'session_id', '');
        $now = date('Y-m-d H:i:s');

        if ($customer_id) {
            $chat = $this->getByField(['customer_id' => $customer_id, 'closed' => 0]);
        } else {
            $chat = $this->getByField(['session_id' => $session_id, 'closed' => 0]);
        }
        if ($chat) { return $chat; }

        $token = substr(bin2hex(random_bytes(16)), 0, 32);
        $title = '';
        $id = $this->insert([
            'customer_id' => $customer_id ?: null,
            'session_id'  => $session_id ?: null,
            'token'       => $token,
            'title'       => $title,
            'created'     => $now,
            'updated'     => $now,
            'closed'      => 0,
        ]);
        return $this->getById($id);
    }
}
