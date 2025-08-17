<?php

namespace TraceCodeMaker\Tests;

use TraceCodeMaker\TraceCodeMaker;
use TraceCodeMaker\TraceCodeMakerServiceProvider;
use Orchestra\Testbench\TestCase;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class TraceCodeMakerTest extends TestCase
{
    protected function getPackageProviders($app)
    {
        return [TraceCodeMakerServiceProvider::class];
    }

    protected function getPackageAliases($app)
    {
        return [
            'TraceCodeMaker' => \TraceCodeMaker\Facades\TraceCodeMaker::class,
        ];
    }

    protected function getEnvironmentSetUp($app)
    {
        // Setup default database to use sqlite :memory:
        $app['config']->set('database.default', 'testbench');
        $app['config']->set('database.connections.testbench', [
            'driver'   => 'sqlite',
            'database' => ':memory:',
            'prefix'   => '',
        ]);

        // Setup cache to use array driver
        $app['config']->set('cache.default', 'array');
    }

    protected function setUp(): void
    {
        parent::setUp();
        
        // Run the migration
        $this->loadMigrationsFrom(__DIR__ . '/../database/migrations');
        
        // Clear cache before each test
        Cache::flush();
    }

    /** @test */
    public function it_can_create_a_new_trace_code()
    {
        $traceCodeMaker = new TraceCodeMaker();
        
        $result = $traceCodeMaker->fetchOrCreateTraceCode(
            'TestService',
            500,
            'testMethod',
            'TestController',
            'Test description'
        );

        $this->assertFalse($result['error']);
        $this->assertNotEmpty($result['traceCode']);
        $this->assertStringContainsString('TES-500-', $result['traceCode']);
    }

    /** @test */
    public function it_returns_existing_trace_code_when_found()
    {
        $traceCodeMaker = new TraceCodeMaker();
        
        // Create first trace code
        $result1 = $traceCodeMaker->fetchOrCreateTraceCode(
            'TestService',
            500,
            'testMethod',
            'TestController'
        );

        // Create second trace code with same parameters
        $result2 = $traceCodeMaker->fetchOrCreateTraceCode(
            'TestService',
            500,
            'testMethod',
            'TestController'
        );

        $this->assertFalse($result1['error']);
        $this->assertFalse($result2['error']);
        $this->assertEquals($result1['traceCode'], $result2['traceCode']);
    }

    /** @test */
    public function it_validates_required_parameters()
    {
        $traceCodeMaker = new TraceCodeMaker();
        
        $result = $traceCodeMaker->fetchOrCreateTraceCode(
            '', // Empty service
            500,
            'testMethod',
            'TestController'
        );

        $this->assertTrue($result['error']);
        $this->assertStringContainsString('validation', strtolower($result['message']));
    }

    /** @test */
    public function it_validates_http_code_range()
    {
        $traceCodeMaker = new TraceCodeMaker();
        
        $result = $traceCodeMaker->fetchOrCreateTraceCode(
            'TestService',
            999, // Invalid HTTP code
            'testMethod',
            'TestController'
        );

        $this->assertTrue($result['error']);
        $this->assertStringContainsString('validation', strtolower($result['message']));
    }

    /** @test */
    public function it_uses_cache_when_enabled()
    {
        Config::set('tracecodemaker.cache.enabled', true);
        
        $traceCodeMaker = new TraceCodeMaker();
        
        // First call should create and cache
        $result1 = $traceCodeMaker->fetchOrCreateTraceCode(
            'CacheTestService',
            404,
            'cacheMethod',
            'CacheController'
        );

        // Verify it's cached
        $cacheKey = $traceCodeMaker->generateCacheKey('CacheTestService', 404, 'cacheMethod', 'CacheController');
        $this->assertTrue(Cache::has($cacheKey));
        
        // Second call should use cache
        $result2 = $traceCodeMaker->fetchOrCreateTraceCode(
            'CacheTestService',
            404,
            'cacheMethod',
            'CacheController'
        );

        $this->assertEquals($result1['traceCode'], $result2['traceCode']);
    }

    /** @test */
    public function it_generates_trace_code_with_correct_format()
    {
        $traceCodeMaker = new TraceCodeMaker();
        
        $result = $traceCodeMaker->fetchOrCreateTraceCode(
            'FormatTestService',
            200,
            'formatMethod',
            'FormatController'
        );

        $this->assertFalse($result['error']);
        
        $traceCode = $result['traceCode'];
        $parts = explode('-', $traceCode);
        
        // Should have 4 parts: SERVICE-HTTP_CODE-METHOD_HASH-TIMESTAMP+SUFFIX
        $this->assertCount(4, $parts);
        $this->assertEquals('FOR', $parts[0]); // First 3 chars of service
        $this->assertEquals('200', $parts[1]); // HTTP code
        $this->assertStringMatchesFormat('%s', $parts[2]); // Method hash
        $this->assertStringMatchesFormat('%s', $parts[3]); // Timestamp + suffix
    }

    /** @test */
    public function it_handles_database_errors_gracefully()
    {
        // Drop the table to simulate database error
        DB::statement('DROP TABLE trace_codes');
        
        $traceCodeMaker = new TraceCodeMaker();
        
        $result = $traceCodeMaker->fetchOrCreateTraceCode(
            'ErrorTestService',
            500,
            'errorMethod',
            'ErrorController'
        );

        $this->assertTrue($result['error']);
        $this->assertStringContainsString('error', strtolower($result['message']));
    }

    /** @test */
    public function it_respects_configuration_settings()
    {
        Config::set('tracecodemaker.format.service_code_length', 5);
        Config::set('tracecodemaker.format.method_hash_length', 8);
        
        $traceCodeMaker = new TraceCodeMaker();
        
        $result = $traceCodeMaker->fetchOrCreateTraceCode(
            'ConfigTestService',
            201,
            'configMethod',
            'ConfigController'
        );

        $this->assertFalse($result['error']);
        
        $traceCode = $result['traceCode'];
        $parts = explode('-', $traceCode);
        
        // Service code should be 5 characters
        $this->assertEquals(5, strlen($parts[0]));
        $this->assertEquals('CONFI', $parts[0]);
    }
}