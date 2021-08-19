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
    private $configs;
    private $functions;
    private $maps;

    public function __construct(string $body = null)
    {
        [$childClass, $caller] = debug_backtrace(false, 2);
        $this->body = $body;
        $this->key = $caller['class'].'::'.$caller['function'];
        $this->topic = config('larakafka.client.client_name');
        $this->headers = array_map([$this, 'flatten'], $caller);
        $this->broker = config('larakafka.client.broker');
        $this->configs = config('larakafka.client.configs');
        $this->functions = config('larakafka.functions');
        $this->maps = config('larakafka.maps');
    }

    private function flatten($value)
    {
        if (is_array($value)) {
            return json_encode($value);
        }

        return $value;
    }

    public function addFunction(string $topic, string $function, string $namespace, string $type = 'consumer'): self
    {
        $this->functions[$type][$topic] = [
            'function' => $function,
            'namespace' => $namespace,
        ];

        return $this;
    }

    public function setBroker(string $broker): self
    {
        $this->broker = $broker;

        return $this;
    }

    public function setProducerConfig(array $config): self
    {
        $this->configs['producer'] = $config;

        return $this;
    }

    public function setConsumerConfig(array $config): self
    {
        $this->configs['consumer'] = $config;

        return $this;
    }

    public function addProducerConfig(string $key, string $value): self
    {
        $this->configs['producer'][$key] = $value;

        return $this;
    }

    public function addConsumerConfig(string $key, string $value): self
    {
        $this->configs['consumer'][$key] = $value;

        return $this;
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
        $producer = $builder->withAdditionalConfig($this->configs['producer'])
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
        $function = $this->functions['consumer'][$this->topic]['function'];
        $namespace = $this->functions['consumer'][$this->topic]['namespace'];
        $nf = $namespace.'::'.$function;
        $nf($key, $headers, $body);
    }

    public function storeMessage(object $attributes, array $types): void
    {
        $config = $this->maps['consumer'];
        foreach ($types as $type) {
            $mapped_attributes = [];
            foreach ($attributes as $key => $value) {
                if (isset($config[$type]['attributes'][$key])) {
                    $mapped_attributes[$config[$type]['attributes'][$key]] = $value;
                }
            }
            $event_id_name = $config[$type]['event_id'];
            $config[$type]['model']::updateOrCreate([$config[$type]['model_id'] => $attributes->$event_id_name], $mapped_attributes);
        }
    }

    public function consume(string $topic)
    {
        $this->topic = $topic;
        $consumer = KafkaConsumerBuilder::create()
            ->withConsumerGroup($this->configs['consumer']['client.id'] ?? 'default')
            ->withAdditionalBroker($this->broker)
            ->withAdditionalConfig($this->configs['consumer'])
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

    public function octaneConsumer(string $topic, $messageCallback, $noMessageCallback)
    {
        $this->topic = $topic;
        $consumer = KafkaConsumerBuilder::create()
            ->withConsumerGroup($this->configs['consumer']['client.id'] ?? 'default')
            ->withAdditionalBroker($this->broker)
            ->withAdditionalConfig($this->configs['consumer'])
            ->withAdditionalSubscription($topic)
            ->build();

        $consumer->subscribe();
        while (true) {
            try {
                $message = $consumer->consume();
                if ($message) {
                    $messageCallback($message);
                }
                $noMessageCallback();
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
