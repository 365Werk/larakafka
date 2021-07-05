<?php

namespace DummyNamespace;

use Jobcloud\Kafka\Message\KafkaConsumerMessage;
use Werk365\LaraKafka\Consumers\BaseConsumer;

class DummyClass extends BaseConsumer
{
    public string $topic = 'TopicName';

    public function handleMessage(KafkaConsumerMessage $message): void
    {
        $key = $message->getKey() ?? null;
        $headers = $message->getHeaders() ?? null;
        $body = $message->getBody();
    }
}
