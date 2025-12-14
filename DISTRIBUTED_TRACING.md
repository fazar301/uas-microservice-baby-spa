# Distributed Tracing Implementation

## Overview
Implementasi distributed tracing menggunakan Correlation ID untuk melacak request di seluruh service.

## Correlation ID Middleware
Middleware `CorrelationIdMiddleware` akan:
- Menerima Correlation ID dari header `X-Correlation-ID` jika ada
- Generate Correlation ID baru (UUID) jika tidak ada
- Menambahkan Correlation ID ke response header
- Menyimpan Correlation ID di request untuk digunakan di logging

## Logging Format
Logging menggunakan format JSON yang konsisten dengan context berikut:
- `correlation_id`: ID untuk melacak request di seluruh service
- `user_id`: ID user yang melakukan request (jika authenticated)
- `user_email`: Email user (jika authenticated)
- `ip_address`: IP address client
- `user_agent`: User agent browser/client
- `method`: HTTP method (GET, POST, etc.)
- `url`: Full URL request

## Contoh Log Output

### 1. User Registration
```json
{
  "timestamp": "2024-01-15 10:30:45.123456",
  "level": "INFO",
  "message": "User registration attempt",
  "context": {
    "email": "user@example.com"
  },
  "correlation_id": "550e8400-e29b-41d4-a716-446655440000",
  "request_id": "550e8400-e29b-41d4-a716-446655440000",
  "user_id": null,
  "user_email": null,
  "ip_address": "127.0.0.1",
  "user_agent": "Mozilla/5.0...",
  "method": "POST",
  "url": "http://localhost:8000/api/register",
  "channel": "json"
}
```

### 2. User Login Success
```json
{
  "timestamp": "2024-01-15 10:31:20.456789",
  "level": "INFO",
  "message": "User logged in successfully",
  "context": {},
  "correlation_id": "550e8400-e29b-41d4-a716-446655440001",
  "request_id": "550e8400-e29b-41d4-a716-446655440001",
  "user_id": 1,
  "user_email": "user@example.com",
  "ip_address": "127.0.0.1",
  "user_agent": "Mozilla/5.0...",
  "method": "POST",
  "url": "http://localhost:8000/api/login",
  "channel": "json"
}
```

### 3. Error Log
```json
{
  "timestamp": "2024-01-15 10:32:10.789012",
  "level": "ERROR",
  "message": "User registration failed",
  "context": {
    "error": "Database connection failed",
    "trace": "..."
  },
  "correlation_id": "550e8400-e29b-41d4-a716-446655440002",
  "request_id": "550e8400-e29b-41d4-a716-446655440002",
  "user_id": null,
  "user_email": null,
  "ip_address": "127.0.0.1",
  "user_agent": "Mozilla/5.0...",
  "method": "POST",
  "url": "http://localhost:8000/api/register",
  "channel": "json"
}
```

## Testing Distributed Tracing

### 1. Test dengan Correlation ID Custom
```bash
curl -X POST http://localhost:8000/api/register \
  -H "Content-Type: application/json" \
  -H "X-Correlation-ID: my-custom-correlation-id-123" \
  -d '{
    "name": "Test User",
    "email": "test@example.com",
    "password": "password123",
    "password_confirmation": "password123"
  }'
```

Response akan memiliki header:
```
X-Correlation-ID: my-custom-correlation-id-123
```

### 2. Test tanpa Correlation ID (Auto-generate)
```bash
curl -X POST http://localhost:8000/api/login \
  -H "Content-Type: application/json" \
  -d '{
    "email": "test@example.com",
    "password": "password123"
  }'
```

Response akan memiliki header dengan UUID yang di-generate otomatis:
```
X-Correlation-ID: 550e8400-e29b-41d4-a716-446655440000
```

## Log Files
- Standard log: `storage/logs/laravel.log`
- JSON log (dengan distributed tracing): `storage/logs/laravel.json`

## Mencari Log berdasarkan Correlation ID
Untuk mencari semua log dari request tertentu:
```bash
grep "550e8400-e29b-41d4-a716-446655440000" storage/logs/laravel.json
```

Semua log dari request yang sama akan memiliki `correlation_id` yang sama, memungkinkan tracing request di seluruh service.

