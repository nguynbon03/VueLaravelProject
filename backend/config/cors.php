<?php

return [
    'paths' => ['api/*', 'sanctum/csrf-cookie'],

    'allowed_methods' => ['*'],

    'allowed_origins' => ['http://localhost:5173', 'http://127.0.0.1:5173', '*'],

    'allowed_origins_patterns' => [],

    'allowed_headers' => [
        'Content-Type',
        'Authorization',
        'Accept',
        'Origin',
        'X-Requested-With',
        'Idempotency-Key',
        'X-Webhook-Signature',
    ],

    'exposed_headers' => [
        'X-Idempotent-Replay',
    ],

    'max_age' => 86400,

    'supports_credentials' => false,
];
