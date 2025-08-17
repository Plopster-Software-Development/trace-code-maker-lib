<?php

namespace Plopster\TraceCodeMaker;

use Illuminate\Support\ServiceProvider;
use Plopster\TraceCodeMaker\Console\Commands\InstallTraceCodeMaker;
use Plopster\TraceCodeMaker\Console\Commands\PublishTraceCodeMaker;

class TraceCodeMakerServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        $this->app->singleton('tracecodemaker', function ($app) {
            return new TraceCodeMaker();
        });

        $this->mergeConfigFrom(
            __DIR__.'/../config/tracecodemaker.php', 'tracecodemaker'
        );
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        // Publish configuration file
        $this->publishes([
            __DIR__.'/../config/tracecodemaker.php' => config_path('tracecodemaker.php'),
        ], 'tracecodemaker-config');

        // Publish migration
        $this->publishes([
            __DIR__.'/../database/migrations/create_trace_codes_table.php.stub' => database_path('migrations/'.date('Y_m_d_His', time()).'_create_trace_codes_table.php'),
        ], 'tracecodemaker-migrations');

        // Load migrations
        $this->loadMigrationsFrom(__DIR__.'/../database/migrations');

        // Register commands
        if ($this->app->runningInConsole()) {
            $this->commands([
                InstallTraceCodeMaker::class,
                PublishTraceCodeMaker::class,
            ]);
        }
    }
}