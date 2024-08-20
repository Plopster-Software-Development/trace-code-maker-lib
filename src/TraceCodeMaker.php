<?php

namespace Plopster\TraceCodeMaker;

use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use Illuminate\Support\Str;

class TraceCodeMaker
{

    /**
     * Fetches an existing trace code based on the provided parameters or creates a new one if it doesn't exist.
     *
     * @param  string      $service      The name of the service generating the trace code.
     * @param  string|int  $httpCode     The HTTP status code associated with the trace code.
     * @param  string|null $description  An optional description for the trace code.
     * @param  string|null     $methodName   The name of the method where the trace code is being generated.
     * @param  string|null     $className    The name of the class where the trace code is being generated.
     * @return array                     An array containing the trace code or an error message.
     */
    public function fetchOrCreateTraceCode(string $service, string|int $httpCode, string $methodName, string $className, ?string $description = null): array
    {
        $description ??= Response::$statusTexts[$this->castToInt($httpCode)];

        $existingTraceCode = $this->findExistingTraceCode($service, $httpCode, $methodName, $className, $description);

        if ($existingTraceCode) {
            return $this->createSuccessResponse($existingTraceCode->trace_code);
        }

        $traceCode = $this->generateTraceCode($service, $httpCode, $methodName, $className);

        return $this->saveTraceCode($service, $httpCode, $methodName, $className, $traceCode, $description);
    }

    /**
     * Searches for an existing trace code in the database using the provided parameters.
     *
     * @param  string      $service    The name of the service generating the trace code.
     * @param  string|int  $httpCode   The HTTP status code associated with the trace code.
     * @param  string      $methodName The name of the method where the trace code is being generated.
     * @param  string      $className  The name of the class where the trace code is being generated.
     * @return object|null             The existing trace code object if found, otherwise null.
     */
    private function findExistingTraceCode(string $service, string|int $httpCode, string $methodName, string $className, string $description): ?object
    {
        return DB::table('trace_codes')
            ->where('service', $service)
            ->where('http_code', $httpCode)
            ->where('method', $methodName)
            ->where('class', $className)
            ->where('description', $description)
            ->first();
    }

    /**
     * Generates a new trace code based on the provided parameters.
     *
     * @param  string      $service    The name of the service generating the trace code.
     * @param  string|int  $httpCode   The HTTP status code associated with the trace code.
     * @param  string      $methodName The name of the method where the trace code is being generated.
     * @param  string      $className  The name of the class where the trace code is being generated.
     * @return string                  The generated trace code.
     */
    private function generateTraceCode(string $service, string|int $httpCode, string $methodName, string $className): string
    {
        $serviceCode = strtoupper(substr($service, 0, 3));
        $methodHash = substr(md5($className . '.' . $methodName), 0, 5);
        $uniqueId = substr(time(), -3);

        return "{$serviceCode}-{$httpCode}-{$methodHash}-{$uniqueId}";
    }

    /**
     * Saves the generated trace code in the database and returns the status of the operation.
     *
     * @param  string      $service      The name of the service generating the trace code.
     * @param  string|int  $httpCode     The HTTP status code associated with the trace code.
     * @param  string      $methodName   The name of the method where the trace code is being generated.
     * @param  string      $className    The name of the class where the trace code is being generated.
     * @param  string      $traceCode    The trace code to be saved.
     * @param  string|null $description  An optional description for the trace code.
     * @return array                     An array containing the trace code or an error message.
     */
    private function saveTraceCode(string $service, string|int $httpCode, string $methodName, string $className, string $traceCode, ?string $description = null): array
    {
        try {
            $inserted = DB::table('trace_codes')->insert([
                'id'          => Str::uuid()->toString(),
                'trace_code'  => $traceCode,
                'service'     => $service,
                'http_code'   => $httpCode,
                'method'      => $methodName,
                'class'       => $className,
                'description' => $description,
                'timestamp'   => Carbon::now()
            ]);

            if (!$inserted) {
                throw new \Exception("Trace code could not be saved, please try again.");
            }

            return $this->createSuccessResponse($traceCode);

        } catch (\Throwable $th) {
            return $this->createErrorResponse($th->getMessage());
        }
    }
    /**
     * Casts a string or integer parameter to an integer.
     *
     * @param  string|int  $param  The parameter to be casted.
     * @return int                 The casted integer value.
     */
    private function castToInt(string|int $param): int
    {
        return is_string($param) ? (int) $param : $param;
    }

    /**
     * Creates a success response array containing the trace code.
     *
     * @param  string  $traceCode  The trace code to be included in the response.
     * @return array               An array indicating success and containing the trace code.
     */
    private function createSuccessResponse(string $traceCode): array
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
    private function createErrorResponse(string $message): array
    {
        return [
            "error"   => true,
            "message" => $message
        ];
    }

}
