
# TraceCodeMaker Library

## Overview

`TraceCodeMaker` is a modern Laravel library designed to create and manage unique trace codes for monitoring, logging, and tracing errors or responses within services. The library features automatic installation, caching support, and comprehensive configuration options.

## Features

- ✅ **Automatic Installation**: Just two commands to get started
- ✅ **Auto-Discovery**: Automatically registers service provider and facade
- ✅ **Caching Support**: Optional caching for improved performance
- ✅ **Configurable**: Extensive configuration options
- ✅ **Validation**: Built-in parameter validation
- ✅ **Database Agnostic**: Works with any Laravel-supported database
- ✅ **Laravel 9, 10, 11, 12 Support**: Compatible with modern Laravel versions

## Requirements

- Laravel 9.x, 10.x, 11.x, or 12.x
- PHP 8.1 or higher

  

## Quick Installation

### Step 1: Install via Composer

```bash
composer require plopster/trace-code-maker
```

### Step 2: Run Installation Command

```bash
php artisan tracecodemaker:install
```

**That's it!** 🎉 The installation command will:
- Publish the configuration file
- Publish and run the migration
- Clear application cache
- Set up everything automatically

## Manual Installation (Alternative)

If you prefer manual installation:

### 1. Install Package
```bash
composer require plopster/trace-code-maker
```

### 2. Publish Files
```bash
# Publish configuration and migrations
php artisan tracecodemaker:publish

# Or publish individually
php artisan tracecodemaker:publish --config
php artisan tracecodemaker:publish --migrations
```

### 3. Run Migration
```bash
php artisan migrate
```

## Configuration

After installation, you can customize the library by editing the published configuration file:

```bash
config/tracecodemaker.php
```

### Configuration Options

#### Default Service
```php
'default_service' => env('TRACE_CODE_DEFAULT_SERVICE', 'DefaultService'),
```
Sets the default service name when none is provided.

#### Trace Code Format
```php
'format' => [
    'service_code_length' => 3,     // Length of service code prefix
    'method_hash_length' => 5,      // Length of method hash
    'random_suffix_length' => 4,    // Length of random suffix
    'timestamp_format' => 'ymdHis', // Timestamp format
],
```
Customize how trace codes are generated.

#### Database Settings
```php
'database' => [
    'connection' => env('TRACE_CODE_DB_CONNECTION', config('database.default')),
    'table' => env('TRACE_CODE_DB_TABLE', 'trace_codes'),
],
```
Configure database connection and table name.

#### Cache Settings
```php
'cache' => [
    'enabled' => env('TRACE_CODE_CACHE_ENABLED', true),
    'ttl' => env('TRACE_CODE_CACHE_TTL', 3600), // 1 hour
    'prefix' => 'tracecode:',
],
```
Enable/disable caching and set cache duration.

#### Validation Rules
```php
'validation' => [
    'service' => 'required|string|max:100',
    'http_code' => 'required|integer|min:100|max:599',
    'method' => 'required|string|max:100',
    'class' => 'required|string|max:255',
    'description' => 'nullable|string|max:500',
],
```
Customize validation rules for input parameters.

### Environment Variables

You can configure the library using environment variables in your `.env` file:

```env
# Default service name
TRACE_CODE_DEFAULT_SERVICE=MyApp

# Cache settings
TRACE_CODE_CACHE_ENABLED=true
TRACE_CODE_CACHE_TTL=3600

# Database settings
TRACE_CODE_DB_CONNECTION=mysql
TRACE_CODE_DB_TABLE=trace_codes
```

### Performance Optimization

#### Caching
The library includes built-in caching to improve performance:
- Trace codes are cached after creation
- Cache keys are generated based on service, HTTP code, method, and class
- Cache TTL is configurable (default: 1 hour)

#### Database Indexes
The migration includes optimized indexes for:
- `trace_code` (unique)
- `service`
- `http_code`
- `method`
- `class`
- `timestamp`

#### Memory Usage
- Validation rules are cached
- Database queries are optimized
- Minimal memory footprint

  

## Usage

After installation, you can use `TraceCodeMaker` anywhere in your Laravel application.

### Basic Usage

```php
use TraceCodeMaker;

$service = 'UserService';
$httpCode = 500;
$methodName = 'createUser';
$className = 'UserController';
$description = 'Internal server error during user creation'; // Optional

$response = TraceCodeMaker::fetchOrCreateTraceCode(
    $service, 
    $httpCode, 
    $methodName, 
    $className, 
    $description
);

if ($response['error']) {
    // Handle error
    logger()->error('TraceCodeMaker error: ' . $response['message']);
} else {
    // Use the trace code
    $traceCode = $response['traceCode'];
    logger()->error('Error occurred', ['trace_code' => $traceCode]);
}
```

### Advanced Usage Examples

#### In Exception Handling

```php
use TraceCodeMaker;
use Illuminate\Http\JsonResponse;

class ApiController extends Controller
{
    public function store(Request $request): JsonResponse
    {
        try {
            // Your business logic here
            return response()->json(['success' => true]);
        } catch (\Exception $e) {
            $traceResponse = TraceCodeMaker::fetchOrCreateTraceCode(
                'ApiService',
                500,
                'store',
                self::class,
                $e->getMessage()
            );
            
            $traceCode = $traceResponse['error'] ? 'UNKNOWN' : $traceResponse['traceCode'];
            
            return response()->json([
                'error' => true,
                'message' => 'Internal server error',
                'trace_code' => $traceCode
            ], 500);
        }
    }
}
```

#### In Middleware

```php
use TraceCodeMaker;

class ErrorTrackingMiddleware
{
    public function handle($request, Closure $next)
    {
        try {
            return $next($request);
        } catch (\Exception $e) {
            $traceResponse = TraceCodeMaker::fetchOrCreateTraceCode(
                'Middleware',
                $e->getCode() ?: 500,
                'handle',
                self::class
            );
            
            if (!$traceResponse['error']) {
                $request->attributes->set('trace_code', $traceResponse['traceCode']);
            }
            
            throw $e;
        }
    }
}
```

#### Using Configuration Defaults

```php
// If you set a default service in config, you can omit it
$response = TraceCodeMaker::fetchOrCreateTraceCode(
    config('tracecodemaker.default_service'), // Uses default from config
    404,
    'show',
    'ProductController'
);
```

### Response Format

The `fetchOrCreateTraceCode` method returns an array with the following structure:

**Success Response:**
```php
[
    'error' => false,
    'traceCode' => 'USR-500-a1b2c-240101123045ABCD'
]
```

**Error Response:**
```php
[
    'error' => true,
    'message' => 'Validation failed: The service field is required.'
]
```

### Trace Code Format

Generated trace codes follow this pattern:
`{SERVICE_CODE}-{HTTP_CODE}-{METHOD_HASH}-{TIMESTAMP}{RANDOM_SUFFIX}`

Example: `USR-500-a1b2c-240101123045ABCD`

- **USR**: First 3 characters of service name (configurable)
- **500**: HTTP status code
- **a1b2c**: MD5 hash of class.method (first 5 chars, configurable)
- **240101123045**: Timestamp in ymdHis format (configurable)
- **ABCD**: Random suffix (4 chars, configurable)

  

## Testing

The library includes comprehensive tests. To run them:

```bash
# Install dev dependencies
composer install --dev

# Run tests
vendor/bin/phpunit

# Run tests with coverage
vendor/bin/phpunit --coverage-html coverage
```

### Test Database

Tests use SQLite in-memory database by default. You can configure this in `phpunit.xml`.

## Troubleshooting

### Common Issues

#### Migration Not Found
```bash
# Publish migrations manually
php artisan tracecodemaker:publish --migrations

# Then run migrations
php artisan migrate
```

#### Cache Issues
```bash
# Clear application cache
php artisan cache:clear

# Clear config cache
php artisan config:clear
```

#### Service Provider Not Registered
For Laravel 10 and below, manually add to `config/app.php`:
```php
'providers' => [
    // ...
    TraceCodeMaker\TraceCodeMakerServiceProvider::class,
],

'aliases' => [
    // ...
    'TraceCodeMaker' => TraceCodeMaker\Facades\TraceCodeMaker::class,
],
```

#### Database Connection Issues
Ensure your database configuration is correct:
```env
TRACE_CODE_DB_CONNECTION=mysql
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=your_database
DB_USERNAME=your_username
DB_PASSWORD=your_password
```

### Debug Mode

Enable debug logging by setting:
```env
APP_DEBUG=true
LOG_LEVEL=debug
```

## Version Compatibility

| Laravel Version | PHP Version | Package Version |
|----------------|-------------|----------------|
| 12.x           | 8.1+        | 1.0+           |
| 11.x           | 8.1+        | 1.0+           |
| 10.x           | 8.1+        | 1.0+           |
| 9.x            | 8.1+        | 1.0+           |

## Contributing

Contributions are welcome! Please follow these steps:

1. Fork the repository
2. Create a feature branch (`git checkout -b feature/amazing-feature`)
3. Commit your changes (`git commit -m 'Add amazing feature'`)
4. Push to the branch (`git push origin feature/amazing-feature`)
5. Open a Pull Request

### Development Setup

```bash
# Clone the repository
git clone https://github.com/yourusername/trace-code-maker-lib.git
cd trace-code-maker-lib

# Install dependencies
composer install

# Run tests
vendor/bin/phpunit
```

### Coding Standards

- Follow PSR-12 coding standards
- Add tests for new features
- Update documentation as needed
- Ensure backward compatibility

## License

This project is open-sourced software licensed under the [MIT license](https://opensource.org/licenses/MIT).

## Support

If you encounter any issues or have questions:

- Check the [troubleshooting section](#troubleshooting)
- Open an issue on GitHub
- Review existing issues and discussions

## Changelog

### v1.0.0
- Initial release
- Laravel 9-12 support
- Auto-discovery support
- Caching mechanism
- Configurable validation
- Artisan commands for installation
- Comprehensive documentation