<?php

namespace Plopster\TraceCodeMaker\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @method static array fetchOrCreateTraceCode(string $service, string|int $httpCode, string $methodName, string $className, ?string $description = null)
 * 
 * @see \Plopster\TraceCodeMaker\TraceCodeMaker
 */
class TraceCodeMaker extends Facade
{
    /**
     * Get the registered name of the component.
     *
     * @return string
     */
    protected static function getFacadeAccessor(): string
    {
        return 'tracecodemaker';
    }
}