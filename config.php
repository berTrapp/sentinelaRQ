<?php

return [
    'rabbitmq' => [
        'host'  => getenv('RABBITMQ_HOST') ?: 'rabbitmq',
        'port'  => (int) (getenv('RABBITMQ_PORT') ?: 5672),
        'user'  => getenv('RABBITMQ_USER') ?: 'admin',
        'pass'  => getenv('RABBITMQ_PASS') ?: 'pass',
        'vhost' => '/',
    ],

    'queue' => 'site_checks',

    'checker' => [
        'timeout' => 10
    ],

    'log_file' => __DIR__ . '/storage/logs.log',
];
