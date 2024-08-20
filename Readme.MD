
# TraceCodeMaker Library

  

## Overview

  

`TraceCodeMaker` is a Laravel library designed to create and manage unique trace codes that can be used to monitor, log, and trace errors or responses within a service. The library allows for fetching an existing trace code based on provided parameters or creating a new one if it does not exist.

  

## Requirements

  

- Laravel 8.x or higher

- PHP 7.4 or higher

- Database with a `trace_codes` table as outlined in the installation instructions.

  

## Installation

  

### 1. Database Setup

  

First, you need to create the `trace_codes` table in your database. You can execute the following command for creating the migration `php artisan make:migration create_trace_codes_table`:

  
The migration must have the following structure
```php

Schema::create('trace_codes', function (Blueprint  $table) {

$table->uuid('id')->primary();

$table->string('trace_code')->unique()->index('trace-code-index');

$table->string('service')->index('restaurant-service-index');

$table->string('http_code')->index('restaurant-http-index');

$table->string('method')->index('restaurant-method-index');

$table->string('class')->index('restaurant-class-index');

$table->string('description');

$table->timestamp('timestamp');

});

```

  

Run the migration to create the table:

  

```bash

php  artisan  migrate

```

  

### 2. Service Provider

  

Next, you need to create a service provider for `TraceCodeMaker` with this command `php artisan  make:provider TraceCodeMakerServiceProvider` and with following structure:

  

```php

namespace  App\Providers;

  

use Illuminate\Support\ServiceProvider;

use Plopster\TraceCodeMaker\TraceCodeMaker;

  

class  TraceCodeMakerServiceProvider  extends  ServiceProvider

{

/**

* Register services.

*/

public  function  register(): void

{

$this->app->singleton('tracecodemaker', function ($app) {

return  new  TraceCodeMaker();

});

}

  

/**

* Bootstrap services.

*/

public  function  boot(): void

{

//

}

}

```

  

Then, if you are using Laravel 11 or upper, the service provider was automatically registered on the `bootstrap\providers.php` file, but if you are using a lower version you must have to register it in the following  path `config/app.php` like this :

  
```php

'providers' => [

// Other Service Providers
App\Providers\TraceCodeMakerServiceProvider::class,
],

```

  

### 3. Facade

  

Create a facade for easier access to the `TraceCodeMaker` functionality. Add the following class to `app/Facades/TraceCodeMaker.php`:

  

```php

namespace  App\Facades;

  

use Illuminate\Support\Facades\Facade;

  

class  TraceCodeMaker  extends  Facade

{

protected  static  function  getFacadeAccessor()

{

return  'tracecodemaker';

}

}

```

  

Finally, if you are using Laravel 10 or Lower you must register the facade in `config/app.php`:

  

```php

'aliases' => [

// Other Facades

  

'TraceCodeMaker' => App\Facades\TraceCodeMaker::class,

],

```

  

## Usage

  

With the setup complete, you can now use the `TraceCodeMaker` in your application to generate or retrieve trace codes.

  

### Example

  

```php

use  TraceCodeMaker;

  

$service  =  'ReservationService';

$httpCode  =  500;

$methodName  =  'reserveTable';

$className  =  'TableController';

  

$response  =  TraceCodeMaker::fetchOrCreateTraceCode($service, $httpCode, $methodName, $className);

  

if ($response['error']) {

// Handle error

echo  $response['message'];

} else {

// Use the trace code

echo  "Trace Code: "  .  $response['traceCode'];

}

```

  

In this example, `fetchOrCreateTraceCode` will return either an existing trace code or create a new one based on the provided parameters. The result will be an array containing the trace code or an error message.

  

## Contributing

  

If you find any issues or have suggestions for improvements, feel free to open an issue or submit a pull request.

  

## License

  

This project is licensed under the MIT License - see the [LICENSE](LICENSE) file for details.