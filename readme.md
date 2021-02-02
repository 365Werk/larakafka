# LaraKafka

[![Latest Version on Packagist][ico-version]][link-packagist]
[![Total Downloads][ico-downloads]][link-downloads]
[![StyleCI][ico-styleci]][link-styleci]

Kafka client package for use in Laravel. Based on [jobcloud/php-kafka-lib](https://github.com/jobcloud/php-kafka-lib).

This package requires [spatie/laravel-activitylog](https://github.com/spatie/laravel-activitylog) to support Kafka based activity logging.

Without much configuration (simply making sure the config has the required broker information and credentials), you'll be able to enable the Spatie Activity Logging on a model, and this package will take care of also sending that information to a kafka topic corresponding with your application name.

Besides this basic logging feature, it also allows you to produce and consume anything you would want. 

Producing can be easily done in-code, and you can start any number of consumers through:
```bash
$ php artisan larakafka:consume {topic}
```
## Installation

Via Composer

``` bash
$ composer require werk365/larakafka
```

Publish the config file using
``` bash
$ php artisan vendor:publish --provider="Werk365\LaraKafka\LaraKafkaServiceProvider"
```

## Configuration
The publishes config file looks as follows, you can find an explanation below it.
```php
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
                'sasl.password' => ''
            ],
            'consumer' => [
                'client.id' => str_replace(' ', '-', strtolower(env('APP_NAME'))),
                'security.protocol' => 'SASL_SSL',
                'sasl.mechanisms' => 'PLAIN',
                'sasl.username' => '',
                'sasl.password' => '',
                'auto.offset.reset' => 'earliest'
            ]
        ],
        'broker' => '',
    ],
    'functions' => [
        'consumer' => [
            'example' => [
                'function' => 'ingest',
                'namespace' => '\App\Services\KafkaService'
            ]
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
                ]
            ]
        ]
    ]
];

```
Not all of the above configuration is needed for every usecase. If you just wish to use the activity logging feature, simply make sure to configure the `client.configs.producer` and `client.broker`.

__Client__

The producer and consumer configuration live here. This should be fairly straight forward. Do note that the default topic set for the producer will be the `client.client_name` value.

__Functions__

This is currently only used for the consumer, if you wish to consume a topic, the corresponding function will be called when a message is received. In this case while consuming the `example` topic, a static function `\App\Services\KafkaService::ingest($key, $headers, $body)` would be called. It is then up to your application to process that data. 

__Maps__

Lastly, this is the configuration for the `storeMessage()` function. This function can be used to help easily process the data received from Kafka. This function expects an array of data and can map and store it to a database for you. Further information about this function in usage, but some configuration points:

`model` = The model that should be used to store the data

`event_id` = The key name of the unique id that belongs to the object

`model_id` = The key name of the unique id as it is called in the model

`attributes` = The attributes that should be stored for this model. Not all attributes configured here have to be present in the consumed message, as only updates attributes could be sent. The `key` represents the attribute key name as it is in the event, the `value` represents the key as it is called in the model. 

## Usage

### 1) Produce Through Activity Logging
Simply enable activity logging on your models according to the [documentation](https://spatie.be/docs/laravel-activitylog/v3/advanced-usage/logging-model-events).

This will enable automatic activity logging to a topic corresponding to your application name.

### 2) Produce Manually
```php
use Werk365\LaraKafka\LaraKafka;

$kafka = new LaraKafka();
$kafka->setTopic("string") //optional, defaults to application name
    ->setKey("string") // optional, default will be the caller classname
    ->setHeaders(["key" => "value"]) // optional, default will contain more information about caller
    ->setBody("string") // Body can also be set like: $kafka = new LaraKafka("body")
    ->produce();
```

__Other available methods:__

`->setBroker("string")` Sets broker other than defined in config

`->setProducerConfig([])` Overrides config settings

`->addProducerConfig("key", "value")` Adds value to set config

`->addHeaders([])` Merges added headers array in to set one


### Consumer
To run a consumer, you can simply run
```bash
$ php artisan larakafka:consume {topic}
```

On reading a message, the function defined in your config will be called. If we have the function given in the example config, it could simply look like this:
```php
namespace App\Services;

use Illuminate\Support\Facades\Log;

class KafkaService

{
    public static function ingest($key, $headers, $body)
    {

        Log::info(json_encode($body));
    }

}
```

If you wish to store the data received in the body, you can use the `storeMessage` method. This method takes in an array of attributes and an array of types. This means one array of attributes can be mapped and stored to different Models (types). In this example we'll only store one model, assuming the example config, and assuming the body has `event_attributes` which is an array containing attributes.
```php
namespace App\Services;

use Werk365\LaraKafka\LaraKafka;

class KafkaService

{
    public static function ingest($key, $headers, $body)
    {
        $kafka = new LaraKafka();
        $kafka->storeMessage($body->event_attributes, ["user"]);
    }

}
```


## Change log

Please see the [changelog](changelog.md) for more information on what has changed recently.

## Testing


WIP

``` bash
$ composer test
```


## Security

If you discover any security related issues, please email author email instead of using the issue tracker.

## Credits

- [Hergen Dillema][link-author]
- [All Contributors][link-contributors]

## License

license. Please see the [license file](license.md) for more information.

[ico-version]: https://img.shields.io/packagist/v/werk365/larakafka.svg?style=flat-square
[ico-downloads]: https://img.shields.io/packagist/dt/werk365/larakafka.svg?style=flat-square
[ico-travis]: https://img.shields.io/travis/werk365/larakafka/master.svg?style=flat-square
[ico-styleci]: https://styleci.io/repos/321376691/shield

[link-packagist]: https://packagist.org/packages/werk365/larakafka
[link-downloads]: https://packagist.org/packages/werk365/larakafka
[link-travis]: https://travis-ci.org/werk365/larakafka
[link-styleci]: https://styleci.io/repos/321376691
[link-author]: https://github.com/HergenD
[link-contributors]: ../../contributors
