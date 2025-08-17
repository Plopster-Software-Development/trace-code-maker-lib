<?php

namespace Plopster\TraceCodeMaker\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;

class InstallTraceCodeMaker extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'tracecodemaker:install {--force : Overwrite existing files}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Install TraceCodeMaker package with all necessary files and migrations';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info('Installing TraceCodeMaker...');
        $this->newLine();

        // Step 1: Publish configuration
        $this->info('Publishing configuration file...');
        $this->call('vendor:publish', [
            '--tag' => 'tracecodemaker-config',
            '--force' => $this->option('force')
        ]);

        // Step 2: Publish migrations
        $this->info('Publishing migration files...');
        $this->call('vendor:publish', [
            '--tag' => 'tracecodemaker-migrations',
            '--force' => $this->option('force')
        ]);

        // Step 3: Run migrations
        $this->info('Running migrations...');
        if ($this->confirm('Do you want to run the migrations now?', true)) {
            $this->call('migrate');
        } else {
            $this->info('Remember to run "php artisan migrate" to create the trace_codes table.');
        }

        // Step 4: Clear cache
        $this->info('Clearing application cache...');
        $this->call('config:clear');
        $this->call('cache:clear');

        $this->newLine();
        $this->info('TraceCodeMaker has been installed successfully!');
        $this->newLine();
        $this->info('You can now use TraceCodeMaker in your application:');
        $this->info('use TraceCodeMaker;');
        $this->info('$result = TraceCodeMaker::fetchOrCreateTraceCode($service, $httpCode, $method, $class);');
        $this->newLine();
        
        $this->info('Configuration file published to: config/tracecodemaker.php');
        $this->info('You can customize the settings according to your needs.');

        return self::SUCCESS;
    }
}