<?php

namespace Werk365\LaraKafka\Interfaces;

use Jobcloud\Kafka\Message\KafkaConsumerMessage;

interface ConsumerInterface
{
    public function handleMessage(KafkaConsumerMessage $message): void;
}
