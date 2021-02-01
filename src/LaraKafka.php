<?php

namespace Werk365\LaraKafka;

use Jobcloud\Kafka\Consumer\KafkaConsumerBuilder;
use Jobcloud\Kafka\Exception\KafkaConsumerConsumeException;
use Jobcloud\Kafka\Exception\KafkaConsumerEndOfPartitionException;
use Jobcloud\Kafka\Exception\KafkaConsumerTimeoutException;
use Jobcloud\Kafka\Message\KafkaProducerMessage;
use Jobcloud\Kafka\Producer\KafkaProducerBuilder;

/**
 * Class LaraKafka.
 */
class LaraKafka
{
    private $body;
    private $topic;
    private $key;
    private $headers;
    private $broker;

    public function __construct(string $body = null)
    {
        [$childClass, $caller] = debug_backtrace(false, 2);
        $this->body = $body;
        $this->key = $caller['class'].'::'.$caller['function'];
        $this->topic = config('larakafka.client');
        $this->headers = array_map([$this, 'flatten'], $caller);
        $this->broker = config('larakafka.broker');
    }

    private function flatten($value)
    {
        if (is_array($value)) {
            return json_encode($value);
        }

        return $value;
    }

    public function setBody(string $body): self
    {
        $this->body = $body;
        return $this;
    }

    public function setKey(string $key): self
    {
        $this->key = $key;
        return $this;
    }

    public function setHeaders(array $headers): self
    {
        $this->headers = $headers;
        return $this;
    }

    public function addHeaders(array $headers): self
    {
        $this->headers = array_merge($this->headers, $headers);
        return $this;
    }

    public function setTopic(string $topic): self
    {
        $this->topic = $topic;
        return $this;
    }

    public function produce(): string
    {
        $builder = KafkaProducerBuilder::create();
        $producer = $builder->withAdditionalConfig(config('larakafka.configs.producer'))
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

        return json_encode(['success' => true]);
    }

    private function handleMessage($message)
    {
        $key = $message->getKey() ?? null;
        $headers = $message->getHeaders() ?? null;
        $body = $message->getBody();
        $function = config("larakafka.consumer.$this->topic.function");
        $namespace = config("larakafka.consumer.$this->topic.namespace");
        $nf = $namespace.'::'.$function;
        $nf($key, $headers, $body);
    }

    public function storeMessage(object $attributes, array $types):void
    {
        $config = config('larakafka.maps.consumer');
        foreach($types as $type){
            $mapped_attributes = [];
            foreach($attributes as $key => $value){
                if(isset($config[$type]["attributes"][$key])){
                    $mapped_attributes[$config[$type]["attributes"][$key]] = $value;
                }
            }
            $event_id_name = $config[$type]["event_id"];
            $config[$type]["model"]::updateOrCreate([$config[$type]["model_id"] => $attributes->$event_id_name], $mapped_attributes);
        }
    }

    public function consume(string $topic)
    {
        $this->topic = $topic;
        $consumer = KafkaConsumerBuilder::create()
            ->withAdditionalBroker($this->broker)
            ->withAdditionalConfig(config('larakafka.configs.consumer'))
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
