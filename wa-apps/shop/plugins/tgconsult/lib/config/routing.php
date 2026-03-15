<?php
return [
    // healthcheck
    'tgconsult/ping/'     => 'frontend/ping',
    'tgconsult/ping'      => 'frontend/ping',

    // Telegram webhook
    'tgconsult/webhook/'  => 'frontend/webhook',
    'tgconsult/webhook'   => 'frontend/webhook',

    // виджетные эндпоинты
    'tgconsult/load/'     => 'frontend/load',
    'tgconsult/load'      => 'frontend/load',
    'tgconsult/send/'     => 'frontend/send',
    'tgconsult/send'      => 'frontend/send',

    // запасной
    'tgconsult/'          => 'frontend/load',
    'tgconsult'           => 'frontend/load',
];
