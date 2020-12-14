<?php

return [
    "client" => str_replace(" ", "-", strtolower(env("APP_NAME"))),
    "additionalConfig" => [
        "client.id" => str_replace(" ", "-", strtolower(env("APP_NAME"))),
        "compression.codec" => "snappy",
        "security.protocol" => "SASL_SSL",
        "sasl.mechanisms" => "PLAIN",
        "sasl.username" => "",
        "sasl.password" => ""
    ],
    "broker" => ""
];
