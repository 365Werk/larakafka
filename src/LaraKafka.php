<?php

namespace Werk365\LaraKafka;

use Illuminate\Support\Facades\Log;
use Jobcloud\Kafka\Consumer\KafkaConsumerBuilder;
use Jobcloud\Kafka\Exception\KafkaConsumerConsumeException;
use Jobcloud\Kafka\Exception\KafkaConsumerEndOfPartitionException;
use Jobcloud\Kafka\Exception\KafkaConsumerTimeoutException;
use Jobcloud\Kafka\Message\KafkaProducerMessage;
use Jobcloud\Kafka\Producer\KafkaProducerBuilder;

/**
 * Class LaraKafka
 * @package Werk365\LaraKafka
 */
class LaraKafka
{
    private $body;
    private $topic;
    private $key;
    private $headers;
    private $broker;
    private $additionalConfig;

    public function __construct($body = null)
    {
        list($childClass, $caller) = debug_backtrace(false, 2);
        $this->body = $body;
        $this->key = $caller["class"] . "::" . $caller["function"];
        $this->topic = config('larakafka.client');
        $this->headers = array_map(array($this, 'flatten'), $caller);
        $this->broker = config('larakafka.broker');
        $this->additionalConfig = config('larakafka.additionalConfig');
    }

    private function flatten($value)
    {
        if (is_array($value)) {
            return json_encode($value);
        }
        return $value;
    }

    public function setBody($body)
    {
        $this->body = $body;
    }

    public function setKey($key)
    {
        $this->key = $key;
    }

    public function setHeaders($headers)
    {
        $this->headers = $headers;
    }

    public function setTopic($topic)
    {
        $this->topic = $topic;
    }

    public function produce()
    {
        $builder = KafkaProducerBuilder::create();
        $producer = $builder->withAdditionalConfig($this->additionalConfig)
            ->withAdditionalBroker($this->broker)
            ->build();

        $message = KafkaProducerMessage::create($this->topic, 0)
            ->withKey(sprintf($this->key))
            ->withBody(sprintf($this->body))
            ->withHeaders($this->headers);
        $producer->produce($message);

        // Shutdown producer, flush messages that are in queue. Give up after 20s
        $result = $producer->flush(20000);

        if (RD_KAFKA_RESP_ERR_NO_ERROR !== $result) {
            return 'Was not able to shutdown within 20s. Messages might be lost!';
        }
        return json_encode(["success" => true]);
    }


    private function handleMessage($message)
    {
        $key = $message->getKey() ?? null;
        $headers = $message->getHeaders() ?? null;
        $body = $message->getBody();
        $function = config("larakafka.consumer.$this->topic.function");
        $namespace = config("larakafka.consumer.$this->topic.namespace");
        $nf = $namespace . "::" . $function;
        $nf($key, $headers, $body);
    }

    public function consume($topic)
    {
        $this->topic = $topic;
        $consumer = KafkaConsumerBuilder::create()
            ->withAdditionalBroker('pkc-ewzgj.europe-west4.gcp.confluent.cloud:9092')
            ->withAdditionalConfig([
                'security.protocol' => 'SASL_SSL',
                'sasl.mechanisms' => 'PLAIN',
                'sasl.username' => 'NEA5ACNQ6IQACNXQ',
                'sasl.password' => 'xYaJgSBHR3mPOqesNFL0iWP6lXJe0h7Y/6cpZaIIHgYT+N10Z9Dvs/qsaPR2WzRD',
                "auto.offset.reset" => "earliest"
            ])
            ->withAdditionalSubscription($topic)
            ->build();

        $consumer->subscribe();
        while (true) {
            try {
                $message = $consumer->consume();
                if ($message) {
                    $this->handleMessage($message);
                }
                $consumer->commit($message);
            } catch (KafkaConsumerTimeoutException $e) {
                //no messages were read in a given time
            } catch (KafkaConsumerEndOfPartitionException $e) {
                //only occurs if enable.partition.eof is true (default: false)
            } catch (KafkaConsumerConsumeException $e) {
                // Failed
            }
        }
    }
}
