# Implementasi Lengkap Final Project - Task A sampai E

## Daftar Isi
1. [Task A: Implementasi Autentikasi](#task-a-implementasi-autentikasi)
2. [Task B: Layanan Tambahan (Reservation Service)](#task-b-layanan-tambahan-reservation-service)
3. [Task C: Booking Service (Service yang Call ke Service Lain)](#task-c-booking-service)
4. [Task D: Middleware Correlation ID](#task-d-middleware-correlation-id)
5. [Task E: Logging Terdistribusi](#task-e-logging-terdistribusi)
6. [Cara Testing Semua Task](#cara-testing-semua-task)

---

## Task A: Implementasi Autentikasi

### ✅ Endpoint yang Diimplementasikan

#### 1. Register
- **Endpoint:** `POST /api/register`
- **Controller:** `App\Http\Controllers\Api\AuthController@register`
- **Request:** `App\Http\Requests\Api\RegisterRequest`
- **Fitur:**
  - Validasi input lengkap (name, email, password, noHP)
  - Auto-verification email (email_verified_at = now())
  - Error handling dengan try-catch
  - Logging dengan correlation ID
  - Return token authentication

**Contoh Request:**
```json
{
  "name": "John Doe",
  "email": "john@example.com",
  "password": "password123",
  "password_confirmation": "password123",
  "noHP": "081234567890"
}
```

**Contoh Response (201):**
```json
{
  "status": "success",
  "message": "User registered successfully",
  "data": {
    "user": {
      "id": 1,
      "name": "John Doe",
      "email": "john@example.com",
      "noHP": "081234567890",
      "role": "customer"
    },
    "token": "1|abc123...",
    "token_type": "Bearer"
  }
}
```

#### 2. Login
- **Endpoint:** `POST /api/login`
- **Controller:** `App\Http\Controllers\Api\AuthController@login`
- **Request:** `App\Http\Requests\Api\LoginRequest`
- **Fitur:**
  - Validasi input (email, password)
  - Error handling untuk invalid credentials
  - Logging dengan correlation ID
  - Return token authentication

**Contoh Request:**
```json
{
  "email": "john@example.com",
  "password": "password123"
}
```

**Contoh Response (200):**
```json
{
  "status": "success",
  "message": "Login successful",
  "data": {
    "user": {
      "id": 1,
      "name": "John Doe",
      "email": "john@example.com",
      "noHP": "081234567890",
      "role": "customer"
    },
    "token": "2|def456...",
    "token_type": "Bearer"
  }
}
```

#### 3. User Profile
- **Endpoint:** `GET /api/profile`
- **Controller:** `App\Http\Controllers\Api\AuthController@profile`
- **Middleware:** `auth:sanctum`
- **Fitur:**
  - Mengembalikan data user yang sedang login
  - Error handling
  - Logging dengan correlation ID

**Contoh Response (200):**
```json
{
  "status": "success",
  "data": {
    "user": {
      "id": 1,
      "name": "John Doe",
      "email": "john@example.com",
      "noHP": "081234567890",
      "role": "customer",
      "email_verified_at": "2024-12-18T10:30:00.000000Z",
      "created_at": "2024-12-18T10:30:00.000000Z",
      "updated_at": "2024-12-18T10:30:00.000000Z"
    }
  }
}
```

#### 4. Update Profile
- **Endpoint:** `PUT /api/profile`
- **Controller:** `App\Http\Controllers\Api\AuthController@update`
- **Request:** `App\Http\Requests\Api\UpdateUserRequest`
- **Middleware:** `auth:sanctum`
- **Fitur:**
  - Validasi input (name, email, password, noHP - semua optional)
  - Error handling
  - Logging dengan correlation ID

**Contoh Request:**
```json
{
  "name": "John Updated",
  "email": "john.updated@example.com"
}
```

#### 5. Logout
- **Endpoint:** `POST /api/logout`
- **Controller:** `App\Http\Controllers\Api\AuthController@logout`
- **Middleware:** `auth:sanctum`
- **Fitur:**
  - Revoke current token
  - Error handling
  - Logging dengan correlation ID

#### 6. User CRUD (Admin Only)
- **List Users:** `GET /api/users`
- **Show User:** `GET /api/users/{id}`
- **Delete User:** `DELETE /api/users/{id}`
- **Controller:** `App\Http\Controllers\Api\AuthController`
- **Middleware:** `auth:sanctum`
- **Authorization:** Admin only (checked in controller)

### ✅ Validasi Input

Semua endpoint menggunakan FormRequest untuk validasi:

**RegisterRequest:**
- name: required, string, min:3, max:255
- email: required, email, unique
- password: required, confirmed, min:8, letters, numbers
- noHP: nullable, string, max:20, unique
- Custom error messages dalam Bahasa Indonesia

**LoginRequest:**
- email: required, email
- password: required, string
- Custom error messages dalam Bahasa Indonesia

**UpdateUserRequest:**
- name: sometimes, string, min:3, max:255
- email: sometimes, email, unique (except current user)
- password: sometimes, confirmed, min:8, letters, numbers
- noHP: sometimes, nullable, string, max:20, unique (except current user)
- Custom error messages dalam Bahasa Indonesia

### ✅ Error Handling

Semua endpoint memiliki error handling yang konsisten:

```php
try {
    // Business logic
    Log::info('Success message', ['correlation_id' => $request->get('correlation_id')]);
    return response()->json(['status' => 'success', ...], 200);
} catch (ValidationException $e) {
    throw $e; // Let Laravel handle validation errors
} catch (\Exception $e) {
    Log::error('Error message', [
        'error' => $e->getMessage(),
        'trace' => $e->getTraceAsString(),
        'correlation_id' => $request->get('correlation_id'),
    ]);
    return response()->json([
        'status' => 'error',
        'message' => 'User-friendly error message',
        'error' => config('app.debug') ? $e->getMessage() : 'Internal server error',
    ], 500);
}
```

### ✅ Unit Test

**File:** `tests/Unit/AuthControllerTest.php`

Test yang diimplementasikan:
1. User registration sets email_verified_at to now (auto-verify)
2. Password is hashed when creating user
3. User has default role of customer
4. User can have admin role
5. User isAdmin method returns false for customer role
6. User can be created with all required fields
7. Password verification works correctly

**Jalankan Test:**
```bash
php artisan test --filter AuthControllerTest
```

---

## Task B: Layanan Tambahan (Reservation Service)

### ✅ CRUD Endpoints

#### 1. List Reservations
- **Endpoint:** `GET /api/reservations`
- **Controller:** `App\Http\Controllers\Api\ReservationApiController@index`
- **Middleware:** `auth:sanctum`
- **Query Parameters:**
  - `status` (optional): Filter by status (pending, confirmed, cancelled)
  - `tanggal_reservasi` (optional): Filter by date
  - Pagination: 15 items per page

**Contoh Response (200):**
```json
{
  "status": "success",
  "data": {
    "current_page": 1,
    "data": [
      {
        "id": 1,
        "user_id": 1,
        "layanan_id": 1,
        "type": "layanan",
        "sesi_id": 1,
        "bayi_id": 1,
        "tanggal_reservasi": "2024-12-20",
        "status": "pending",
        "harga": 100000.00,
        "kode": "RSV-ABC123"
      }
    ],
    "total": 10
  }
}
```

#### 2. Create Reservation
- **Endpoint:** `POST /api/reservations`
- **Controller:** `App\Http\Controllers\Api\ReservationApiController@store`
- **Request:** `App\Http\Requests\Api\CreateReservationRequest`
- **Middleware:** `auth:sanctum`

**Contoh Request:**
```json
{
  "service_id": 1,
  "type": "layanan",
  "sesi_id": 1,
  "tanggal_reservasi": "2024-12-20",
  "catatan": "Catatan khusus",
  "baby_id": 1
}
```

**Atau dengan baby_data:**
```json
{
  "service_id": 1,
  "type": "layanan",
  "sesi_id": 1,
  "tanggal_reservasi": "2024-12-20",
  "baby_data": {
    "nama": "Baby Name",
    "tanggal_lahir": "2024-06-01",
    "jenis_kelamin": "L",
    "berat_lahir": 3.5,
    "berat_sekarang": 7.0,
    "is_temporary": false
  }
}
```

#### 3. Show Reservation
- **Endpoint:** `GET /api/reservations/{id}`
- **Controller:** `App\Http\Controllers\Api\ReservationApiController@show`
- **Middleware:** `auth:sanctum`

#### 4. Update Reservation
- **Endpoint:** `PUT /api/reservations/{id}`
- **Controller:** `App\Http\Controllers\Api\ReservationApiController@update`
- **Request:** `App\Http\Requests\Api\UpdateReservationRequest`
- **Middleware:** `auth:sanctum`
- **Business Rule:** Hanya bisa update jika status = 'pending'

**Contoh Request:**
```json
{
  "sesi_id": 2,
  "tanggal_reservasi": "2024-12-21",
  "catatan": "Updated catatan"
}
```

#### 5. Delete Reservation
- **Endpoint:** `DELETE /api/reservations/{id}`
- **Controller:** `App\Http\Controllers\Api\ReservationApiController@destroy`
- **Middleware:** `auth:sanctum`
- **Business Rule:** Hanya bisa delete jika status = 'pending'

### ✅ Validasi Input

**CreateReservationRequest:**
- service_id: required, integer, exists:layanans,id
- type: required, string, in:layanan,paket
- sesi_id: required, integer, exists:sesis,id
- tanggal_reservasi: required, date, after_or_equal:today
- catatan: nullable, string, max:500
- baby_id: nullable, integer, exists:bayis,id
- baby_data: nullable, array (required if baby_id not provided)
- Custom error messages dalam Bahasa Indonesia

**UpdateReservationRequest:**
- sesi_id: sometimes, integer, exists:sesis,id
- tanggal_reservasi: sometimes, date, after_or_equal:today
- catatan: nullable, string, max:500
- baby_id: nullable, integer, exists:bayis,id

### ✅ Error Handling

Semua endpoint memiliki error handling konsisten dengan:
- Try-catch blocks
- Database transaction untuk create/update/delete
- Logging error dengan correlation ID
- User-friendly error messages
- Proper HTTP status codes

### ✅ Unit Test

**File:** `tests/Unit/ReservationApiControllerTest.php`

Test yang diimplementasikan:
1. Reservation can be created with valid data
2. Reservation has default status of pending when created
3. Reservation belongs to user
4. Reservation can have catatan field

**Jalankan Test:**
```bash
php artisan test --filter ReservationApiControllerTest
```

---

## Task C: Booking Service (Service yang Call ke Service Lain)

### ✅ Endpoints

#### 1. Create Booking
- **Endpoint:** `POST /api/bookings`
- **Controller:** `App\Http\Controllers\Api\BookingServiceController@createBooking`
- **Middleware:** `auth:sanctum`
- **Fitur:**
  - Call ke User Service untuk verify user
  - Call ke Reservation Service untuk create reservation
  - Mengirim Correlation ID ke kedua service
  - Meneruskan Authorization token ke kedua service
  - Error handling konsisten untuk kegagalan service lain

**Contoh Request:**
```json
{
  "service_id": 1,
  "type": "layanan",
  "sesi_id": 1,
  "tanggal_reservasi": "2024-12-20",
  "catatan": "Catatan booking",
  "baby_id": 1
}
```

**Contoh Response (201):**
```json
{
  "status": "success",
  "message": "Booking created successfully",
  "data": {
    "user": {
      "id": 1,
      "name": "John Doe",
      "email": "john@example.com"
    },
    "reservation": {
      "id": 1,
      "kode": "RSV-ABC123",
      "status": "pending",
      "harga": 100000.00
    }
  }
}
```

**Contoh Response jika User Service gagal (503):**
```json
{
  "status": "error",
  "message": "Failed to verify user. Please try again.",
  "service_error": "user_service",
  "error": "User service is unavailable"
}
```

#### 2. Get Booking
- **Endpoint:** `GET /api/bookings/{id}`
- **Controller:** `App\Http\Controllers\Api\BookingServiceController@getBooking`
- **Middleware:** `auth:sanctum`
- **Fitur:**
  - Call ke User Service untuk get user info
  - Call ke Reservation Service untuk get reservation info
  - Mengirim Correlation ID ke kedua service
  - Meneruskan Authorization token ke kedua service

### ✅ Correlation ID Passing

Service C mengirim Correlation ID ke service lain melalui header:

```php
$headers = [
    'X-Correlation-ID' => $correlationId,
    'Authorization' => "Bearer {$authToken}",
];

$response = Http::withHeaders($headers)
    ->get($url);
```

### ✅ Authorization Token Passing

Service C meneruskan Authorization token ke service lain:

```php
if ($authToken) {
    $headers['Authorization'] = str_starts_with($authToken, 'Bearer ') 
        ? $authToken 
        : "Bearer {$authToken}";
}
```

### ✅ Error Handling Konsisten

Service C memiliki error handling konsisten untuk kegagalan service lain:

```php
if (!$userServiceResponse['success']) {
    return response()->json([
        'status' => 'error',
        'message' => 'Failed to verify user. Please try again.',
        'service_error' => 'user_service',
        'error' => config('app.debug') ? $userServiceResponse['error'] : 'User service unavailable',
    ], 503);
}
```

**Error Types:**
- `503 Service Unavailable`: Service lain tidak tersedia
- `404 Not Found`: Resource tidak ditemukan di service lain
- `500 Internal Server Error`: Error internal di service lain
- Connection timeout: Ditangani dengan try-catch

### ✅ Unit Test

**File:** `tests/Unit/BookingServiceControllerTest.php`

Test yang diimplementasikan:
1. Booking service can handle user service call
2. Booking service handles user service failure gracefully
3. Booking service passes correlation ID correctly
4. Booking service passes authorization token correctly
5. Booking service handles connection timeout gracefully

**Jalankan Test:**
```bash
php artisan test --filter BookingServiceControllerTest
```

---

## Task D: Middleware Correlation ID

### ✅ Implementasi

**File:** `app/Http/Middleware/CorrelationIdMiddleware.php`

**Fungsi:**
- Menerima Correlation ID dari header `X-Correlation-ID` jika ada
- Generate Correlation ID baru (UUID) jika tidak ada
- Menyimpan Correlation ID di request untuk digunakan di logging
- Menambahkan Correlation ID ke response header

**Registrasi Middleware:**

**File:** `bootstrap/app.php`
```php
->withMiddleware(function (Middleware $middleware) {
    $middleware->api(prepend: [
        \App\Http\Middleware\CorrelationIdMiddleware::class,
    ]);
    $middleware->web(prepend: [
        \App\Http\Middleware\CorrelationIdMiddleware::class,
    ]);
})
```

**File:** `app/Providers/Filament/AdminPanelProvider.php`
```php
->middleware([
    // ... other middleware
    \App\Http\Middleware\CorrelationIdMiddleware::class,
])
```

### ✅ Coverage

Middleware Correlation ID sudah terdaftar di:
- ✅ API routes (semua `/api/*`)
- ✅ Web routes (semua web routes)
- ✅ Filament admin panel (semua `/admin/*`)

### ✅ Cara Kerja

1. Request masuk dengan atau tanpa header `X-Correlation-ID`
2. Middleware mengecek header, jika tidak ada generate UUID baru
3. Correlation ID disimpan di `$request->merge(['correlation_id' => $correlationId])`
4. Correlation ID ditambahkan ke response header `X-Correlation-ID`
5. Semua log menggunakan correlation ID dari request

---

## Task E: Logging Terdistribusi

### ✅ Log Context

Setiap log entry memiliki context lengkap yang ditambahkan oleh `CorrelationIdProcessor`:

**File:** `app/Logging/CorrelationIdProcessor.php`

**Context yang ditambahkan:**
- `correlation_id`: ID untuk melacak request di seluruh service
- `request_id`: Sama dengan correlation_id
- `user_id`: ID user yang melakukan request (jika authenticated)
- `user_email`: Email user (jika authenticated)
- `ip_address`: IP address client
- `user_agent`: User agent browser/client
- `method`: HTTP method (GET, POST, etc.)
- `url`: Full URL request

### ✅ Logging Format Konsisten

Semua log menggunakan format JSON yang konsisten melalui `JsonFormatter`:

**File:** `app/Logging/JsonFormatter.php`

**Format:**
```json
{
  "timestamp": "2024-12-18 10:30:15.123456",
  "level": "INFO",
  "message": "User logged in successfully",
  "context": {
    "user_id": 1,
    "email": "john@example.com"
  },
  "correlation_id": "550e8400-e29b-41d4-a716-446655440000",
  "request_id": "550e8400-e29b-41d4-a716-446655440000",
  "user_id": 1,
  "user_email": "john@example.com",
  "ip_address": "127.0.0.1",
  "user_agent": "PostmanRuntime/7.32.3",
  "method": "POST",
  "url": "http://localhost:8000/api/login",
  "channel": "json"
}
```

### ✅ Konfigurasi Logging

**File:** `config/logging.php`

Channel `json` dikonfigurasi dengan:
- `JsonFormatter`: Format JSON konsisten
- `CorrelationIdProcessor`: Menambahkan context ke setiap log
- Log disimpan di `storage/logs/laravel.json`

### ✅ Proof of Distributed Tracing

#### Contoh 1: Tracing Booking Creation Request

**Correlation ID:** `abc123-def456-ghi789`

**1. Booking Service - Request Masuk**
```json
{
  "timestamp": "2024-12-18 14:30:15.123456",
  "level": "INFO",
  "message": "Booking creation attempt",
  "context": {
    "correlation_id": "abc123-def456-ghi789",
    "user_id": 1
  },
  "correlation_id": "abc123-def456-ghi789",
  "request_id": "abc123-def456-ghi789",
  "user_id": 1,
  "user_email": "john@example.com",
  "ip_address": "192.168.1.100",
  "method": "POST",
  "url": "http://localhost:8000/api/bookings",
  "channel": "json"
}
```

**2. Booking Service - Call User Service**
```json
{
  "timestamp": "2024-12-18 14:30:15.234567",
  "level": "INFO",
  "message": "Calling user service",
  "context": {
    "url": "http://localhost:8000/api/users/1",
    "correlation_id": "abc123-def456-ghi789",
    "user_id": 1
  },
  "correlation_id": "abc123-def456-ghi789",
  "request_id": "abc123-def456-ghi789",
  "user_id": 1,
  "user_email": "john@example.com",
  "ip_address": "192.168.1.100",
  "method": "GET",
  "url": "http://localhost:8000/api/bookings",
  "channel": "json"
}
```

**3. User Service - Request Diterima**
```json
{
  "timestamp": "2024-12-18 14:30:15.345678",
  "level": "INFO",
  "message": "User details retrieved",
  "context": {
    "user_id": 1,
    "target_user_id": 1,
    "correlation_id": "abc123-def456-ghi789"
  },
  "correlation_id": "abc123-def456-ghi789",
  "request_id": "abc123-def456-ghi789",
  "user_id": 1,
  "user_email": "john@example.com",
  "ip_address": "192.168.1.100",
  "method": "GET",
  "url": "http://localhost:8000/api/users/1",
  "channel": "json"
}
```

**4. Booking Service - Call Reservation Service**
```json
{
  "timestamp": "2024-12-18 14:30:15.456789",
  "level": "INFO",
  "message": "Calling reservation service",
  "context": {
    "url": "http://localhost:8000/api/reservations",
    "correlation_id": "abc123-def456-ghi789"
  },
  "correlation_id": "abc123-def456-ghi789",
  "request_id": "abc123-def456-ghi789",
  "user_id": 1,
  "user_email": "john@example.com",
  "ip_address": "192.168.1.100",
  "method": "POST",
  "url": "http://localhost:8000/api/bookings",
  "channel": "json"
}
```

**5. Reservation Service - Request Diterima**
```json
{
  "timestamp": "2024-12-18 14:30:15.567890",
  "level": "INFO",
  "message": "Reservation creation attempt",
  "context": {
    "user_id": 1,
    "service_id": 1,
    "type": "layanan",
    "correlation_id": "abc123-def456-ghi789"
  },
  "correlation_id": "abc123-def456-ghi789",
  "request_id": "abc123-def456-ghi789",
  "user_id": 1,
  "user_email": "john@example.com",
  "ip_address": "192.168.1.100",
  "method": "POST",
  "url": "http://localhost:8000/api/reservations",
  "channel": "json"
}
```

**6. Reservation Service - Reservation Created**
```json
{
  "timestamp": "2024-12-18 14:30:15.678901",
  "level": "INFO",
  "message": "Reservation created successfully",
  "context": {
    "user_id": 1,
    "reservation_id": 5,
    "correlation_id": "abc123-def456-ghi789"
  },
  "correlation_id": "abc123-def456-ghi789",
  "request_id": "abc123-def456-ghi789",
  "user_id": 1,
  "user_email": "john@example.com",
  "ip_address": "192.168.1.100",
  "method": "POST",
  "url": "http://localhost:8000/api/reservations",
  "channel": "json"
}
```

**7. Booking Service - Booking Created Successfully**
```json
{
  "timestamp": "2024-12-18 14:30:15.789012",
  "level": "INFO",
  "message": "Booking created successfully",
  "context": {
    "correlation_id": "abc123-def456-ghi789",
    "user_id": 1,
    "reservation_id": 5
  },
  "correlation_id": "abc123-def456-ghi789",
  "request_id": "abc123-def456-ghi789",
  "user_id": 1,
  "user_email": "john@example.com",
  "ip_address": "192.168.1.100",
  "method": "POST",
  "url": "http://localhost:8000/api/bookings",
  "channel": "json"
}
```

**Semua log di atas memiliki `correlation_id` yang sama: `abc123-def456-ghi789`**

#### Contoh 2: Tracing dengan Service Failure

**Correlation ID:** `xyz789-abc123-def456`

**1. Booking Service - Call User Service**
```json
{
  "timestamp": "2024-12-18 14:35:20.123456",
  "level": "INFO",
  "message": "Calling user service",
  "correlation_id": "xyz789-abc123-def456",
  "user_id": 1,
  "method": "GET",
  "url": "http://localhost:8000/api/users/1",
  "channel": "json"
}
```

**2. User Service - Connection Failed**
```json
{
  "timestamp": "2024-12-18 14:35:20.234567",
  "level": "ERROR",
  "message": "User service connection failed",
  "context": {
    "error": "Connection timeout",
    "correlation_id": "xyz789-abc123-def456"
  },
  "correlation_id": "xyz789-abc123-def456",
  "request_id": "xyz789-abc123-def456",
  "user_id": 1,
  "method": "GET",
  "url": "http://localhost:8000/api/users/1",
  "channel": "json"
}
```

**3. Booking Service - Error Response**
```json
{
  "timestamp": "2024-12-18 14:35:20.345678",
  "level": "ERROR",
  "message": "User service call failed",
  "context": {
    "correlation_id": "xyz789-abc123-def456",
    "error": "User service is unavailable"
  },
  "correlation_id": "xyz789-abc123-def456",
  "request_id": "xyz789-abc123-def456",
  "user_id": 1,
  "method": "POST",
  "url": "http://localhost:8000/api/bookings",
  "channel": "json"
}
```

### ✅ Cara Melacak Request dengan Correlation ID

```bash
# Mencari semua log dengan correlation ID tertentu
grep "abc123-def456-ghi789" storage/logs/laravel.json

# Atau menggunakan jq untuk format yang lebih rapi
cat storage/logs/laravel.json | jq 'select(.correlation_id == "abc123-def456-ghi789")'
```

---

## Cara Testing Semua Task

### Prerequisites

1. Install dependencies:
```bash
composer install
npm install
```

2. Setup database:
```bash
cp .env.example .env
php artisan key:generate
php artisan migrate
php artisan db:seed
```

3. Start server:
```bash
php artisan serve
```

### Testing Task A: Autentikasi

#### 1. Test Register

```bash
curl -X POST http://localhost:8000/api/register \
  -H "Content-Type: application/json" \
  -H "X-Correlation-ID: test-register-123" \
  -d '{
    "name": "Test User",
    "email": "test@example.com",
    "password": "password123",
    "password_confirmation": "password123",
    "noHP": "081234567890"
  }'
```

**Expected Response:**
- Status: 201
- Header: `X-Correlation-ID: test-register-123`
- Body: JSON dengan user data dan token

#### 2. Test Login

```bash
curl -X POST http://localhost:8000/api/login \
  -H "Content-Type: application/json" \
  -H "X-Correlation-ID: test-login-123" \
  -d '{
    "email": "test@example.com",
    "password": "password123"
  }'
```

**Expected Response:**
- Status: 200
- Header: `X-Correlation-ID: test-login-123`
- Body: JSON dengan user data dan token

**Simpan token untuk test berikutnya:**
```bash
TOKEN="your-token-here"
```

#### 3. Test Profile

```bash
curl -X GET http://localhost:8000/api/profile \
  -H "Authorization: Bearer $TOKEN" \
  -H "X-Correlation-ID: test-profile-123"
```

#### 4. Test Update Profile

```bash
curl -X PUT http://localhost:8000/api/profile \
  -H "Authorization: Bearer $TOKEN" \
  -H "Content-Type: application/json" \
  -H "X-Correlation-ID: test-update-123" \
  -d '{
    "name": "Updated Name"
  }'
```

#### 5. Test User CRUD (Admin)

**Login sebagai admin:**
```bash
curl -X POST http://localhost:8000/api/login \
  -H "Content-Type: application/json" \
  -d '{
    "email": "admin@latumi.com",
    "password": "admin123"
  }'
```

**List Users:**
```bash
curl -X GET http://localhost:8000/api/users \
  -H "Authorization: Bearer $ADMIN_TOKEN" \
  -H "X-Correlation-ID: test-users-list-123"
```

**Show User:**
```bash
curl -X GET http://localhost:8000/api/users/1 \
  -H "Authorization: Bearer $ADMIN_TOKEN" \
  -H "X-Correlation-ID: test-user-show-123"
```

**Delete User:**
```bash
curl -X DELETE http://localhost:8000/api/users/2 \
  -H "Authorization: Bearer $ADMIN_TOKEN" \
  -H "X-Correlation-ID: test-user-delete-123"
```

#### 6. Run Unit Tests

```bash
php artisan test --filter AuthControllerTest
```

### Testing Task B: Reservation Service

#### 1. Test Create Reservation

```bash
curl -X POST http://localhost:8000/api/reservations \
  -H "Authorization: Bearer $TOKEN" \
  -H "Content-Type: application/json" \
  -H "X-Correlation-ID: test-reservation-create-123" \
  -d '{
    "service_id": 1,
    "type": "layanan",
    "sesi_id": 1,
    "tanggal_reservasi": "2024-12-20",
    "catatan": "Test reservation",
    "baby_id": 1
  }'
```

#### 2. Test List Reservations

```bash
curl -X GET http://localhost:8000/api/reservations \
  -H "Authorization: Bearer $TOKEN" \
  -H "X-Correlation-ID: test-reservations-list-123"
```

#### 3. Test Show Reservation

```bash
curl -X GET http://localhost:8000/api/reservations/1 \
  -H "Authorization: Bearer $TOKEN" \
  -H "X-Correlation-ID: test-reservation-show-123"
```

#### 4. Test Update Reservation

```bash
curl -X PUT http://localhost:8000/api/reservations/1 \
  -H "Authorization: Bearer $TOKEN" \
  -H "Content-Type: application/json" \
  -H "X-Correlation-ID: test-reservation-update-123" \
  -d '{
    "sesi_id": 2,
    "tanggal_reservasi": "2024-12-21"
  }'
```

#### 5. Test Delete Reservation

```bash
curl -X DELETE http://localhost:8000/api/reservations/1 \
  -H "Authorization: Bearer $TOKEN" \
  -H "X-Correlation-ID: test-reservation-delete-123"
```

#### 6. Run Unit Tests

```bash
php artisan test --filter ReservationApiControllerTest
```

### Testing Task C: Booking Service

#### 1. Test Create Booking

```bash
curl -X POST http://localhost:8000/api/bookings \
  -H "Authorization: Bearer $TOKEN" \
  -H "Content-Type: application/json" \
  -H "X-Correlation-ID: test-booking-create-123" \
  -d '{
    "service_id": 1,
    "type": "layanan",
    "sesi_id": 1,
    "tanggal_reservasi": "2024-12-20",
    "catatan": "Test booking",
    "baby_id": 1
  }'
```

**Expected Behavior:**
- Service C akan call User Service dengan Correlation ID yang sama
- Service C akan call Reservation Service dengan Correlation ID yang sama
- Semua log akan memiliki Correlation ID yang sama

#### 2. Test Get Booking

```bash
curl -X GET http://localhost:8000/api/bookings/1 \
  -H "Authorization: Bearer $TOKEN" \
  -H "X-Correlation-ID: test-booking-get-123"
```

#### 3. Test Error Handling (Service Failure)

**Simulasikan User Service failure dengan mengubah URL di BookingServiceController sementara:**
- Ubah URL user service ke URL yang tidak ada
- Test create booking
- Verify error response dengan `service_error: "user_service"`

#### 4. Verify Correlation ID Passing

**Check logs untuk verify Correlation ID diteruskan:**
```bash
grep "test-booking-create-123" storage/logs/laravel.json | jq '.correlation_id'
```

**Expected:** Semua log memiliki correlation_id yang sama

#### 5. Verify Authorization Token Passing

**Check logs untuk verify token diteruskan:**
- Log di User Service dan Reservation Service harus menunjukkan user_id yang benar
- Ini berarti token berhasil diteruskan dan user ter-authenticate

#### 6. Run Unit Tests

```bash
php artisan test --filter BookingServiceControllerTest
```

### Testing Task D: Correlation ID Middleware

#### 1. Test dengan Custom Correlation ID

```bash
curl -X GET http://localhost:8000/api/profile \
  -H "Authorization: Bearer $TOKEN" \
  -H "X-Correlation-ID: my-custom-correlation-id"
```

**Expected:**
- Response header: `X-Correlation-ID: my-custom-correlation-id`
- Log memiliki correlation_id: `my-custom-correlation-id`

#### 2. Test tanpa Correlation ID (Auto-generate)

```bash
curl -X GET http://localhost:8000/api/profile \
  -H "Authorization: Bearer $TOKEN"
```

**Expected:**
- Response header: `X-Correlation-ID: <UUID>`
- Log memiliki correlation_id: `<UUID>`

#### 3. Test di Web Routes

Buka browser dan akses:
- `http://localhost:8000/login`
- `http://localhost:8000/register`
- `http://localhost:8000/dashboard`

**Check logs:**
```bash
tail -f storage/logs/laravel.json | jq '.correlation_id'
```

**Expected:** Setiap request memiliki correlation_id

#### 4. Test di Filament Admin

Login ke Filament admin:
- `http://localhost:8000/admin`

Akses berbagai resource dan check logs untuk verify correlation_id ada di semua request.

### Testing Task E: Logging Terdistribusi

#### 1. Test Log Context

Buat request dan check log:

```bash
curl -X POST http://localhost:8000/api/login \
  -H "Content-Type: application/json" \
  -H "X-Correlation-ID: test-log-context-123" \
  -d '{
    "email": "test@example.com",
    "password": "password123"
  }'
```

**Check log:**
```bash
grep "test-log-context-123" storage/logs/laravel.json | jq '.'
```

**Expected:** Log memiliki semua context:
- correlation_id
- user_id (setelah login)
- user_email
- ip_address
- user_agent
- method
- url

#### 2. Test Log Format Konsisten

Check beberapa log entries:

```bash
cat storage/logs/laravel.json | jq '.[0:3]'
```

**Expected:** Semua log memiliki format JSON yang sama dengan field:
- timestamp
- level
- message
- context
- correlation_id
- request_id
- user_id
- user_email
- ip_address
- user_agent
- method
- url
- channel

#### 3. Test Distributed Tracing

**Buat booking request:**
```bash
curl -X POST http://localhost:8000/api/bookings \
  -H "Authorization: Bearer $TOKEN" \
  -H "Content-Type: application/json" \
  -H "X-Correlation-ID: test-tracing-123" \
  -d '{
    "service_id": 1,
    "type": "layanan",
    "sesi_id": 1,
    "tanggal_reservasi": "2024-12-20",
    "baby_id": 1
  }'
```

**Trace semua log dengan correlation ID:**
```bash
grep "test-tracing-123" storage/logs/laravel.json | jq '.message, .correlation_id'
```

**Expected:** Semua log dari request yang sama memiliki correlation_id yang sama

**Count log entries:**
```bash
grep "test-tracing-123" storage/logs/laravel.json | wc -l
```

**Expected:** Minimal 5-7 log entries (booking service, user service, reservation service)

### Running All Tests

```bash
# Run semua unit tests
php artisan test --filter Unit

# Run semua feature tests
php artisan test --filter Feature

# Run semua tests
php artisan test
```

---

## File-File yang Terlibat

### Task A (Autentikasi)
- `app/Http/Controllers/Api/AuthController.php`
- `app/Http/Controllers/UserController.php` (Web)
- `app/Http/Controllers/ProfileController.php` (Web)
- `app/Http/Controllers/Auth/AuthenticatedSessionController.php` (Web)
- `app/Http/Controllers/Auth/RegisteredUserController.php` (Web)
- `app/Http/Requests/Api/RegisterRequest.php`
- `app/Http/Requests/Api/LoginRequest.php`
- `app/Http/Requests/Api/UpdateUserRequest.php`
- `routes/api.php`
- `routes/web.php`
- `tests/Unit/AuthControllerTest.php`
- `tests/Feature/Api/AuthApiTest.php`
- `app/Filament/Resources/UserResource.php` (Filament)

### Task B (Reservation Service)
- `app/Http/Controllers/Api/ReservationApiController.php`
- `app/Http/Requests/Api/CreateReservationRequest.php`
- `app/Http/Requests/Api/UpdateReservationRequest.php`
- `routes/api.php`
- `tests/Unit/ReservationApiControllerTest.php`

### Task C (Booking Service)
- `app/Http/Controllers/Api/BookingServiceController.php`
- `routes/api.php`
- `tests/Unit/BookingServiceControllerTest.php`

### Task D (Correlation ID Middleware)
- `app/Http/Middleware/CorrelationIdMiddleware.php`
- `bootstrap/app.php`
- `app/Providers/Filament/AdminPanelProvider.php`

### Task E (Distributed Logging)
- `app/Logging/CorrelationIdProcessor.php`
- `app/Logging/JsonFormatter.php`
- `config/logging.php`

---

## Kesimpulan

✅ **Task A** - Implementasi autentikasi lengkap dengan:
- Endpoint register, login, profile, CRUD
- Validasi input lengkap
- Error handling konsisten
- Unit test minimal 1

✅ **Task B** - Layanan tambahan (Reservation Service) dengan:
- CRUD lengkap
- Validasi input lengkap
- Error handling konsisten
- Unit test minimal 1

✅ **Task C** - Booking Service dengan:
- Call ke User Service dan Reservation Service
- Mengirim dan menerima Correlation ID
- Meneruskan Authorization token
- Error handling konsisten untuk kegagalan service lain
- Unit test minimal 1

✅ **Task D** - Middleware Correlation ID di:
- API routes
- Web routes
- Filament admin panel

✅ **Task E** - Logging terdistribusi dengan:
- Log context lengkap
- Logging format konsisten (JSON)
- Proof of distributed tracing (dokumentasi dengan contoh log)

---

## Dokumentasi Tambahan

- `IMPLEMENTATION_TASK_A_E.md` - Dokumentasi task A dan E (sebelumnya)
- `IMPLEMENTATION_WEB_AUTH.md` - Dokumentasi autentikasi web
- `DISTRIBUTED_TRACING.md` - Dokumentasi distributed tracing detail

