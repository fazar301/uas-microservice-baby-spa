<?php

namespace App\Logging;

use Monolog\Formatter\JsonFormatter as BaseJsonFormatter;

class JsonFormatter extends BaseJsonFormatter
{
    /**
     * Formats a log record with consistent structure
     */
    public function format(array $record): string
    {
        $formatted = [
            'timestamp' => $record['datetime']->format('Y-m-d H:i:s.u'),
            'level' => $record['level_name'],
            'message' => $record['message'],
            'context' => $record['context'] ?? [],
            'correlation_id' => $record['extra']['correlation_id'] ?? null,
            'request_id' => $record['extra']['request_id'] ?? null,
            'user_id' => $record['extra']['user_id'] ?? null,
            'user_email' => $record['extra']['user_email'] ?? null,
            'ip_address' => $record['extra']['ip_address'] ?? null,
            'user_agent' => $record['extra']['user_agent'] ?? null,
            'method' => $record['extra']['method'] ?? null,
            'url' => $record['extra']['url'] ?? null,
            'channel' => $record['channel'] ?? 'default',
        ];

        return json_encode($formatted, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) . "\n";
    }
}

