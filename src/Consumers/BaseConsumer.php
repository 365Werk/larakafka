<?php

namespace Werk365\LaraKafka\Consumers;

use Laravel\Octane\Facades\Octane;
use Werk365\LaraKafka\Interfaces\ConsumerInterface;
use Werk365\LaraKafka\LaraKafka;

abstract class BaseConsumer implements ConsumerInterface
{
    public $messageCallback;
    public $noMessageCallback;

    public function __construct()
    {
        $this->messageCallback = function ($message) {
            $this->handleMessage($message);
        };
        $this->noMessageCallback = function () {
            $tick_time = time();
            Octane::table('kafka')->set($this->topic, [
                'running' => 1,
                'last_tick' => $tick_time,
            ]);
        };
    }

    /**
     * Handle the event.
     *
     * @param mixed $event
     * @return void
     */
    public function handle(): void
    {
        $tick_time = time();
        $running = Octane::table('kafka')->get($this->topic);
        if ($running && $running['running'] === 1) {
            if ($running['last_tick'] > $tick_time - 60) {
                return;
            }
        }

        Octane::table('kafka')->set($this->topic, [
            'running' => 1,
            'last_tick' => $tick_time,
        ]);

        $kafka = new LaraKafka();
        $kafka->octaneConsumer($this->topic, $this->messageCallback, $this->noMessageCallback);
    }
}
