<?php

namespace Werk365\LaraKafka\Commands;


use Illuminate\Console\Command;
use Werk365\LaraKafka\LaraKafka;

class LaraKafkaConsume extends Command
{
    /**
     * The name and signature of the console command.
     * @var string
     */
    protected $signature = 'larakafka:consume {topic}';

    /**
     * The console command description.
     * @var string
     */
    protected $description = 'Consume Kafka Topic';

    public function handle()
    {
        $topic = $this->argument('topic');
        $kafka = new LaraKafka();
        $kafka->consume($topic);
    }
}
