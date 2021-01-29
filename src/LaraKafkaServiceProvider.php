<?php

namespace Werk365\LaraKafka;

use Illuminate\Support\ServiceProvider;
use Spatie\Activitylog\Models\Activity as ActivityLog;
use Werk365\LaraKafka\Observers\ActivityLogObserver;

class LaraKafkaServiceProvider extends ServiceProvider
{
    protected $commands = [
        'Werk365\LaraKafka\Commands\LaraKafkaConsume',
    ];

    /**
     * Perform post-registration booting of services.
     *
     * @return void
     */
    public function boot(): void
    {
        ActivityLog::observe(ActivityLogObserver::class);
        // $this->loadTranslationsFrom(__DIR__.'/../resources/lang', 'werk365');
        // $this->loadViewsFrom(__DIR__.'/../resources/views', 'werk365');
        // $this->loadMigrationsFrom(__DIR__.'/../database/migrations');
        // $this->loadRoutesFrom(__DIR__.'/routes.php');

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
     * Get the services provided by the provider.
     *
     * @return array
     */
    public function provides()
    {
        return ['larakafka'];
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

        // Publishing the views.
        /*$this->publishes([
            __DIR__.'/../resources/views' => base_path('resources/views/vendor/werk365'),
        ], 'larakafka.views');*/

        // Publishing assets.
        /*$this->publishes([
            __DIR__.'/../resources/assets' => public_path('vendor/werk365'),
        ], 'larakafka.views');*/

        // Publishing the translation files.
        /*$this->publishes([
            __DIR__.'/../resources/lang' => resource_path('lang/vendor/werk365'),
        ], 'larakafka.views');*/

        // Registering package commands.
        // $this->commands([]);
    }
}
