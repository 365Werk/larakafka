<?php

return [
    'client' => [
        'client_name' =>  str_replace(' ', '-', strtolower(env('APP_NAME'))),
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
                'client.id' => str_replace(' ', '-', strtolower(env('APP_NAME'))),
                'security.protocol' => 'SASL_SSL',
                'sasl.mechanisms' => 'PLAIN',
                'sasl.username' => '',
                'sasl.password' => '',
                'auto.offset.reset' => 'earliest',
            ],
        ],
        'broker' => '',
    ],
    'functions' => [
        'consumer' => [
            'example' => [
                'function' => 'ingest',
                'namespace' => '\App\Services\KafkaService',
            ],
        ],
    ],
    'maps' => [
        'consumer' => [
            'example' => [
                'model' => 'App\Models\Example',
                'event_id' => 'uuid',
                'model_id' => 'id',
                'attributes' => [
                    'uuid' => 'id',
                    'first_name' => 'name',
                ],
            ],
        ],
    ],
];
