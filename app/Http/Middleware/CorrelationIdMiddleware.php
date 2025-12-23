<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;

class CorrelationIdMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Get correlation ID from request header or generate new one
        $correlationId = $request->header('X-Correlation-ID') ?? (string) Str::uuid();

        // Store correlation ID in request for later use
        $request->merge(['correlation_id' => $correlationId]);

        // Add correlation ID to response header
        $response = $next($request);
        $response->headers->set('X-Correlation-ID', $correlationId);

        return $response;
    }
}

