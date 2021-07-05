<?php

namespace Werk365\LaraKafka;

use Illuminate\Support\ServiceProvider;
use Spatie\Activitylog\Models\Activity as ActivityLog;
use Werk365\LaraKafka\Observers\ActivityLogObserver;

class LaraKafkaServiceProvider extends ServiceProvider
{
    protected $commands = [
        'Werk365\LaraKafka\Commands\LaraKafkaConsume',
        'Werk365\LaraKafka\Commands\MakeConsumer',
    ];

    /**
     * Perform post-registration booting of services.
     *
     * @return void
     */
    public function boot(): void
    {
        ActivityLog::observe(ActivityLogObserver::class);

        // Publishing is only necessary when using the CLI.
        if ($this->app->runningInConsole()) {
            $this->bootForConsole();
        }
    }

    /**
     * Register any package services.
     *
     * @return void
     */
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../config/larakafka.php', 'larakafka');

        // Register the service the package provides.
        $this->app->singleton('larakafka', function ($app) {
            return new LaraKafka;
        });
        $this->commands($this->commands);
    }

    /**
     * Console-specific booting.
     *
     * @return void
     */
    protected function bootForConsole(): void
    {
        // Publishing the configuration file.
        $this->publishes([
            __DIR__.'/../config/larakafka.php' => config_path('larakafka.php'),
        ], 'larakafka.config');
    }
}
