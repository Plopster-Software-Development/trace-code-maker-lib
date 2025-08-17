<?php

namespace Plopster\TraceCodeMaker;

use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Validator;
use Carbon\Carbon;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Exception;

class TraceCodeMaker
{

    /**
     * Fetches an existing trace code based on the provided parameters or creates a new one if it doesn't exist.
     *
     * @param  string      $service      The name of the service generating the trace code.
     * @param  string|int  $httpCode     The HTTP status code associated with the trace code.
     * @param  string      $methodName   The name of the method where the trace code is being generated.
     * @param  string      $className    The name of the class where the trace code is being generated.
     * @param  string|null $description  An optional description for the trace code.
     * @return array                     An array containing the trace code or an error message.
     */
    public static function fetchOrCreateTraceCode(string $service, string|int $httpCode, string $methodName, string $className, ?string $description = null): array
    {
        try {
            // Validate input parameters
            self::validateParameters($service, $httpCode, $methodName, $className, $description);
            
            $httpCodeInt = self::castToInt($httpCode);
            $description ??= Response::$statusTexts[$httpCodeInt] ?? 'Unknown Status';

            // Check cache first if enabled
            if (config('tracecodemaker.cache.enabled', false)) {
                $cacheKey = self::generateCacheKey($service, $httpCodeInt, $methodName, $className, $description);
                $cachedTraceCode = Cache::get($cacheKey);
                
                if ($cachedTraceCode) {
                    return self::createSuccessResponse($cachedTraceCode);
                }
            }

            $existingTraceCode = self::findExistingTraceCode($service, $httpCodeInt, $methodName, $className, $description);

            if ($existingTraceCode) {
                $traceCode = $existingTraceCode->trace_code;
                
                // Cache the result if caching is enabled
                if (config('tracecodemaker.cache.enabled', false)) {
                    Cache::put($cacheKey, $traceCode, config('tracecodemaker.cache.ttl', 3600));
                }
                
                return self::createSuccessResponse($traceCode);
            }

            $traceCode = self::generateTraceCode($service, $httpCodeInt, $methodName, $className);

            return self::saveTraceCode($service, $httpCodeInt, $methodName, $className, $traceCode, $description);
            
        } catch (ValidationException $e) {
            return self::createErrorResponse('Validation failed: ' . $e->getMessage());
        } catch (Exception $e) {
            return self::createErrorResponse('An error occurred: ' . $e->getMessage());
        }
    }

    /**
     * Searches for an existing trace code in the database using the provided parameters.
     *
     * @param  string $service     The name of the service generating the trace code.
     * @param  int    $httpCode    The HTTP status code associated with the trace code.
     * @param  string $methodName  The name of the method where the trace code is being generated.
     * @param  string $className   The name of the class where the trace code is being generated.
     * @param  string $description The description for the trace code.
     * @return object|null         The existing trace code object if found, otherwise null.
     */
    private static function findExistingTraceCode(string $service, int $httpCode, string $methodName, string $className, string $description): ?object
    {
        $tableName = config('tracecodemaker.database.table', 'trace_codes');
        $connection = config('tracecodemaker.database.connection');
        
        $query = DB::connection($connection)->table($tableName)
            ->where('service', $service)
            ->where('http_code', $httpCode)
            ->where('method', $methodName)
            ->where('class', $className)
            ->where('description', $description);
            
        return $query->first();
    }

    /**
     * Validates input parameters according to configuration rules.
     *
     * @param  string      $service     The service name to validate.
     * @param  string|int  $httpCode    The HTTP code to validate.
     * @param  string      $methodName  The method name to validate.
     * @param  string      $className   The class name to validate.
     * @param  string|null $description The description to validate.
     * @throws ValidationException
     */
    private static function validateParameters(string $service, string|int $httpCode, string $methodName, string $className, ?string $description = null): void
    {
        $rules = [
            'service' => 'required|string|max:' . config('tracecodemaker.validation.service_max_length', 50),
            'http_code' => 'required|integer|min:100|max:599',
            'method_name' => 'required|string|max:' . config('tracecodemaker.validation.method_max_length', 100),
            'class_name' => 'required|string|max:' . config('tracecodemaker.validation.class_max_length', 255),
            'description' => 'nullable|string|max:' . config('tracecodemaker.validation.description_max_length', 500),
        ];

        $data = [
            'service' => $service,
            'http_code' => self::castToInt($httpCode),
            'method_name' => $methodName,
            'class_name' => $className,
            'description' => $description,
        ];

        $validator = Validator::make($data, $rules);

        if ($validator->fails()) {
            throw new ValidationException($validator);
        }
    }

    /**
     * Generates a cache key for the given parameters.
     *
     * @param  string $service     The service name.
     * @param  int    $httpCode    The HTTP code.
     * @param  string $methodName  The method name.
     * @param  string $className   The class name.
     * @param  string $description The description.
     * @return string              The generated cache key.
     */
    private static function generateCacheKey(string $service, int $httpCode, string $methodName, string $className, string $description): string
    {
        $prefix = config('tracecodemaker.cache.prefix', 'trace_code_');
        $key = md5($service . $httpCode . $methodName . $className . $description);
        
        return $prefix . $key;
    }

    /**
     * Generates a new trace code based on the provided parameters.
     *
     * @param  string $service    The name of the service generating the trace code.
     * @param  int    $httpCode   The HTTP status code associated with the trace code.
     * @param  string $methodName The name of the method where the trace code is being generated.
     * @param  string $className  The name of the class where the trace code is being generated.
     * @return string             The generated trace code.
     */
    private static function generateTraceCode(string $service, int $httpCode, string $methodName, string $className): string
    {
        $format = config('tracecodemaker.format', []);
        
        $serviceCodeLength = $format['service_code_length'] ?? 3;
        $methodHashLength = $format['method_hash_length'] ?? 5;
        $randomSuffixLength = $format['random_suffix_length'] ?? 4;
        $timestampFormat = $format['timestamp_format'] ?? 'ymdHis';
        
        $serviceCode = strtoupper(substr($service, 0, $serviceCodeLength));
        $methodHash = substr(md5($className . '.' . $methodName), 0, $methodHashLength);
        $randomSuffix = strtoupper(Str::random($randomSuffixLength));
        $timestamp = now()->format($timestampFormat);

        return "{$serviceCode}-{$httpCode}-{$methodHash}-{$timestamp}{$randomSuffix}";
    }

    /**
     * Saves the generated trace code in the database and returns the status of the operation.
     *
     * @param  string      $service      The name of the service generating the trace code.
     * @param  int         $httpCode     The HTTP status code associated with the trace code.
     * @param  string      $methodName   The name of the method where the trace code is being generated.
     * @param  string      $className    The name of the class where the trace code is being generated.
     * @param  string      $traceCode    The trace code to be saved.
     * @param  string|null $description  An optional description for the trace code.
     * @return array                     An array containing the trace code or an error message.
     */
    private static function saveTraceCode(string $service, int $httpCode, string $methodName, string $className, string $traceCode, ?string $description = null): array
    {
        try {
            $tableName = config('tracecodemaker.database.table', 'trace_codes');
            $connection = config('tracecodemaker.database.connection');
            
            $inserted = DB::connection($connection)->table($tableName)->insert([
                'id'          => Str::uuid()->toString(),
                'trace_code'  => $traceCode,
                'service'     => $service,
                'http_code'   => $httpCode,
                'method'      => $methodName,
                'class'       => $className,
                'description' => $description,
                'timestamp'   => Carbon::now(),
                'created_at'  => Carbon::now(),
                'updated_at'  => Carbon::now()
            ]);

            if (!$inserted) {
                throw new Exception("Trace code could not be saved, please try again.");
            }

            // Cache the new trace code if caching is enabled
            if (config('tracecodemaker.cache.enabled', false)) {
                $cacheKey = self::generateCacheKey($service, $httpCode, $methodName, $className, $description);
                Cache::put($cacheKey, $traceCode, config('tracecodemaker.cache.ttl', 3600));
            }

            return self::createSuccessResponse($traceCode);

        } catch (\Throwable $th) {
            return self::createErrorResponse($th->getMessage());
        }
    }
    /**
     * Casts a string or integer parameter to an integer.
     *
     * @param  string|int  $param  The parameter to be casted.
     * @return int                 The casted integer value.
     */
    private static function castToInt(string|int $param): int
    {
        return is_string($param) ? (int) $param : $param;
    }

    /**
     * Creates a success response array containing the trace code.
     *
     * @param  string  $traceCode  The trace code to be included in the response.
     * @return array               An array indicating success and containing the trace code.
     */
    private static function createSuccessResponse(string $traceCode): array
    {
        return [
            "error"     => false,
            "traceCode" => $traceCode
        ];
    }

    /**
     * Creates an error response array containing the error message.
     *
     * @param  string  $message  The error message to be included in the response.
     * @return array             An array indicating an error and containing the error message.
     */
    private static function createErrorResponse(string $message): array
    {
        return [
            "error"   => true,
            "message" => $message
        ];
    }

}
