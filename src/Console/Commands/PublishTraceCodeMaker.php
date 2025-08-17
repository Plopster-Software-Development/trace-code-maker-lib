<?php

namespace Plopster\TraceCodeMaker\Console\Commands;

use Illuminate\Console\Command;

class PublishTraceCodeMaker extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'tracecodemaker:publish 
                            {--config : Publish only the configuration file}
                            {--migrations : Publish only the migration files}
                            {--force : Overwrite existing files}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Publish TraceCodeMaker configuration and migration files';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $publishConfig = $this->option('config');
        $publishMigrations = $this->option('migrations');
        $force = $this->option('force');

        // If no specific option is provided, publish everything
        if (!$publishConfig && !$publishMigrations) {
            $publishConfig = true;
            $publishMigrations = true;
        }

        $this->info('📦 Publishing TraceCodeMaker files...');
        $this->newLine();

        if ($publishConfig) {
            $this->info('📝 Publishing configuration file...');
            $this->call('vendor:publish', [
                '--tag' => 'tracecodemaker-config',
                '--force' => $force
            ]);
        }

        if ($publishMigrations) {
            $this->info('📊 Publishing migration files...');
            $this->call('vendor:publish', [
                '--tag' => 'tracecodemaker-migrations',
                '--force' => $force
            ]);
        }

        $this->newLine();
        $this->info('✅ Files published successfully!');
        
        if ($publishMigrations) {
            $this->newLine();
            $this->comment('Don\'t forget to run the migrations:');
            $this->line('php artisan migrate');
        }

        return self::SUCCESS;
    }
}