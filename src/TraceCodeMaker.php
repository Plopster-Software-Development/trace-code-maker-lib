<?php

namespace Plopster\TraceCodeMaker;

use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use Illuminate\Support\Str;

class TraceCodeMaker
{
    public function generateTraceCode(string $service, string|int $httpCode, string $methodName, string $className, ?string $description = null): array
    {
        $serviceCode = strtoupper(substr($service, 0, 3));
        $methodHash = substr(md5($className . '.' . $methodName), 0, 5);
        $uniqueId = substr(time(), -3);

        $traceCode = "{$serviceCode}-{$httpCode}-{$methodHash}-{$uniqueId}";

        return $this->saveTraceCode($service, $httpCode, $methodName, $className, $traceCode, $description);
    }

    private function saveTraceCode(string $service, string|int $httpCode, string $methodName, string $className, string $traceCode, ?string $description = null)
    {
        try {
            if (!$description) {
                $description = Response::$statusTexts[$this->castToInt($httpCode)];
            }

            DB::table('trace_codes')->insert([
                'id'          => Str::uuid()->toString(),
                'trace_code'  => $traceCode,
                'service'     => $service,
                'http_code'   => $httpCode,
                'method'      => $methodName,
                'class'       => $className,
                'description' => $description,
                'timestamp'   => Carbon::now()
            ]);

            return [
                "error"     => false,
                "traceCode" => $traceCode
            ];
        } catch (\Throwable $th) {
            return [
                "error"   => true,
                "message" => $th->getMessage()
            ];
        }
    }

    private function castToInt(string|int $param)
    {
        return is_string($param) ? (int) $param : $param;
    }

}
