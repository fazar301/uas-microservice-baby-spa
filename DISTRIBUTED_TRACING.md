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

## Proof of Distributed Tracing

Berikut adalah contoh nyata bagaimana Correlation ID digunakan untuk melacak request di seluruh service. Semua log dari request yang sama memiliki `correlation_id` yang sama, memungkinkan kita untuk melacak perjalanan request dari awal sampai akhir.

### Contoh: Tracing User Registration Request

Request dengan Correlation ID: `abc123-def456-ghi789`

#### 1. Request Masuk (Middleware)
```json
{
  "timestamp": "2024-12-15 14:30:15.123456",
  "level": "INFO",
  "message": "Request received",
  "context": {},
  "correlation_id": "abc123-def456-ghi789",
  "request_id": "abc123-def456-ghi789",
  "user_id": null,
  "user_email": null,
  "ip_address": "192.168.1.100",
  "user_agent": "PostmanRuntime/7.32.3",
  "method": "POST",
  "url": "http://localhost:8000/api/register",
  "channel": "json"
}
```

#### 2. Validasi Request (FormRequest)
```json
{
  "timestamp": "2024-12-15 14:30:15.234567",
  "level": "INFO",
  "message": "User registration attempt",
  "context": {
    "email": "newuser@example.com",
    "correlation_id": "abc123-def456-ghi789"
  },
  "correlation_id": "abc123-def456-ghi789",
  "request_id": "abc123-def456-ghi789",
  "user_id": null,
  "user_email": null,
  "ip_address": "192.168.1.100",
  "user_agent": "PostmanRuntime/7.32.3",
  "method": "POST",
  "url": "http://localhost:8000/api/register",
  "channel": "json"
}
```

#### 3. User Berhasil Dibuat (Controller)
```json
{
  "timestamp": "2024-12-15 14:30:15.345678",
  "level": "INFO",
  "message": "User registered successfully",
  "context": {
    "user_id": 42,
    "email": "newuser@example.com",
    "correlation_id": "abc123-def456-ghi789"
  },
  "correlation_id": "abc123-def456-ghi789",
  "request_id": "abc123-def456-ghi789",
  "user_id": 42,
  "user_email": "newuser@example.com",
  "ip_address": "192.168.1.100",
  "user_agent": "PostmanRuntime/7.32.3",
  "method": "POST",
  "url": "http://localhost:8000/api/register",
  "channel": "json"
}
```

#### 4. Response Dikirim (Middleware)
```json
{
  "timestamp": "2024-12-15 14:30:15.456789",
  "level": "INFO",
  "message": "Response sent",
  "context": {
    "status_code": 201
  },
  "correlation_id": "abc123-def456-ghi789",
  "request_id": "abc123-def456-ghi789",
  "user_id": 42,
  "user_email": "newuser@example.com",
  "ip_address": "192.168.1.100",
  "user_agent": "PostmanRuntime/7.32.3",
  "method": "POST",
  "url": "http://localhost:8000/api/register",
  "channel": "json"
}
```

### Contoh: Tracing Login Request dengan Error

Request dengan Correlation ID: `xyz789-abc123-def456`

#### 1. Login Attempt
```json
{
  "timestamp": "2024-12-15 14:35:20.123456",
  "level": "INFO",
  "message": "User login attempt",
  "context": {
    "email": "user@example.com",
    "correlation_id": "xyz789-abc123-def456"
  },
  "correlation_id": "xyz789-abc123-def456",
  "request_id": "xyz789-abc123-def456",
  "user_id": null,
  "user_email": null,
  "ip_address": "192.168.1.100",
  "user_agent": "PostmanRuntime/7.32.3",
  "method": "POST",
  "url": "http://localhost:8000/api/login",
  "channel": "json"
}
```

#### 2. Invalid Credentials Warning
```json
{
  "timestamp": "2024-12-15 14:35:20.234567",
  "level": "WARNING",
  "message": "Login failed: Invalid credentials",
  "context": {
    "email": "user@example.com",
    "correlation_id": "xyz789-abc123-def456"
  },
  "correlation_id": "xyz789-abc123-def456",
  "request_id": "xyz789-abc123-def456",
  "user_id": null,
  "user_email": null,
  "ip_address": "192.168.1.100",
  "user_agent": "PostmanRuntime/7.32.3",
  "method": "POST",
  "url": "http://localhost:8000/api/login",
  "channel": "json"
}
```

### Cara Melacak Request dengan Correlation ID

Untuk melacak semua log dari request tertentu, gunakan command berikut:

```bash
# Mencari semua log dengan correlation ID tertentu
grep "abc123-def456-ghi789" storage/logs/laravel.json

# Atau menggunakan jq untuk format yang lebih rapi
cat storage/logs/laravel.json | jq 'select(.correlation_id == "abc123-def456-ghi789")'
```

### Keuntungan Distributed Tracing

1. **Traceability**: Setiap request dapat dilacak dari awal sampai akhir
2. **Debugging**: Mudah menemukan semua log terkait dengan request tertentu
3. **Monitoring**: Dapat melacak performa dan error di seluruh service
4. **Consistency**: Format log yang konsisten memudahkan parsing dan analisis
5. **Context**: Setiap log memiliki context lengkap (user, IP, method, URL, dll)

### Integration dengan Service Lain

Ketika service ini memanggil service lain (misalnya melalui HTTP client), Correlation ID harus diteruskan melalui header:

```php
use Illuminate\Support\Facades\Http;

$response = Http::withHeaders([
    'X-Correlation-ID' => request()->get('correlation_id'),
    'Authorization' => 'Bearer ' . $token,
])->post('http://other-service/api/endpoint', $data);
```

Dengan cara ini, semua log dari service yang berbeda akan memiliki Correlation ID yang sama, memungkinkan tracing request di seluruh microservices architecture.

