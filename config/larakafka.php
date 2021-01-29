<?php

return [
    'client' => str_replace(' ', '-', strtolower(env('APP_NAME'))),
    'configs' => [
        'producer' => [
            'client.id' => str_replace(' ', '-', strtolower(env('APP_NAME'))),
            'compression.codec' => 'snappy',
            'security.protocol' => 'SASL_SSL',
            'sasl.mechanisms' => 'PLAIN',
            'sasl.username' => '',
            'sasl.password' => '',
        ],
        'consumer' => [
            'security.protocol' => 'SASL_SSL',
            'sasl.mechanisms' => 'PLAIN',
            'sasl.username' => '',
            'sasl.password' => '',
            'auto.offset.reset' => 'earliest',
        ],
    ],
    'broker' => '',
    'consumer' => [
        'communication' => [
            'function' => 'ingest',
            'namespace' => "\App\Services\CommunicationKafkaService",
        ],
    ],
];
