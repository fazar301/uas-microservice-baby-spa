<?php

namespace App\Logging;

use Monolog\Processor\ProcessorInterface;
use Illuminate\Support\Facades\Request;

class CorrelationIdProcessor implements ProcessorInterface
{
    /**
     * Add correlation ID to log context
     * Compatible with both Monolog 2.x (array) and 3.x (LogRecord)
     */
    public function __invoke(array $record): array
    {
        $correlationId = Request::get('correlation_id') 
            ?? Request::header('X-Correlation-ID') 
            ?? 'no-correlation-id';

        if (!isset($record['extra'])) {
            $record['extra'] = [];
        }

        $record['extra']['correlation_id'] = $correlationId;
        $record['extra']['request_id'] = $correlationId;
        
        // Add user information if authenticated
        if (auth()->check()) {
            $record['extra']['user_id'] = auth()->id();
            $record['extra']['user_email'] = auth()->user()->email ?? null;
        }

        // Add request information
        $record['extra']['ip_address'] = Request::ip();
        $record['extra']['user_agent'] = Request::userAgent();
        $record['extra']['method'] = Request::method();
        $record['extra']['url'] = Request::fullUrl();

        return $record;
    }
}

