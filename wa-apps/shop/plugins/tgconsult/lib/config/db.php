<?php
return [
  'shop_tgconsult_chat' => [
    'id'           => ['int', 11, 'null' => 0, 'auto_increment' => 1],
    'customer_id'  => ['int', 11, 'null' => 1],
    'session_id'   => ['varchar', 64, 'null' => 1],
    'token'        => ['varchar', 32, 'null' => 0],
    'title'        => ['varchar', 255, 'null' => 1], // Диалог №N от даты
    'created'      => ['datetime', 'null' => 0],
    'updated'      => ['datetime', 'null' => 0],
    'closed'       => ['tinyint', 1, 'null' => 0, 'default' => '0'],
    ':keys'        => [
      'PRIMARY'         => 'id',
      'token'           => ['token', 'unique' => 1],
      'customer_id'     => 'customer_id',
      'created'         => 'created',
    ],
  ],
  'shop_tgconsult_message' => [
    'id'           => ['int', 11, 'null' => 0, 'auto_increment' => 1],
    'chat_id'      => ['int', 11, 'null' => 0],
    'sender'       => ['enum', "'visitor','manager'", 'null' => 0],
    'text'         => ['text', 'null' => 0],
    'payload'      => ['text', 'null' => 1], // json (tg_message_id и т.п.)
    'tg_message_id'=> ['bigint', 20, 'null' => 1],
    'created'      => ['datetime', 'null' => 0],
    ':keys'        => [
      'PRIMARY' => 'id',
      'chat_id' => 'chat_id',
      'created' => 'created',
    ],
  ],
];
