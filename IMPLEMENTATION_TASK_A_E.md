# Implementasi Tugas A dan E - Final Project

## Tugas A: Implementasi Autentikasi pada Layanan User

### ✅ Endpoint yang Diimplementasikan

1. **Register** - `POST /api/register`
   - Validasi input lengkap (name, email, password, noHP)
   - Auto-verification email (email_verified_at = now()) karena tidak ada SMTP API
   - Error handling dengan try-catch
   - Logging dengan correlation ID
   - Return token authentication

2. **Login** - `POST /api/login`
   - Validasi input (email, password)
   - Error handling untuk invalid credentials
   - Logging dengan correlation ID
   - Return token authentication

3. **User Profile** - `GET /api/profile`
   - Mengembalikan data user yang sedang login
   - Error handling
   - Logging dengan correlation ID

4. **Update Profile** - `PUT /api/profile`
   - Validasi input (name, email, password, noHP - semua optional)
   - Error handling
   - Logging dengan correlation ID

5. **Logout** - `POST /api/logout`
   - Revoke current token
   - Error handling
   - Logging dengan correlation ID

6. **User CRUD (Admin Only)**
   - **List Users** - `GET /api/users` (Admin only)
   - **Show User** - `GET /api/users/{id}` (Admin only)
   - **Delete User** - `DELETE /api/users/{id}` (Admin only)
   - Semua memiliki error handling dan logging

### ✅ Validasi Input

Semua endpoint menggunakan FormRequest untuk validasi:

- **RegisterRequest** (`app/Http/Requests/Api/RegisterRequest.php`)
  - name: required, string, min:3, max:255
  - email: required, email, unique
  - password: required, confirmed, min:8, letters, numbers
  - noHP: nullable, string, max:20, unique
  - Custom error messages dalam Bahasa Indonesia

- **LoginRequest** (`app/Http/Requests/Api/LoginRequest.php`)
  - email: required, email
  - password: required, string
  - Custom error messages dalam Bahasa Indonesia

- **UpdateUserRequest** (`app/Http/Requests/Api/UpdateUserRequest.php`)
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

Unit test telah dibuat di `tests/Unit/AuthControllerTest.php` yang menguji:

1. User registration dengan email_verified_at auto-set
2. Password hashing
3. Default role assignment (customer)
4. Admin role functionality
5. Password verification
6. User creation dengan semua field required

**File:** `tests/Unit/AuthControllerTest.php`

**Feature tests** juga tersedia di `tests/Feature/Api/AuthApiTest.php` yang menguji:
- Register endpoint
- Login endpoint
- Profile endpoints
- User CRUD endpoints
- Correlation ID handling

### ✅ Auto-Verification Email

Pada endpoint register, email otomatis di-verify karena tidak ada SMTP API:

```php
$user = User::create([
    'name' => $request->name,
    'email' => $request->email,
    'password' => Hash::make($request->password),
    'noHP' => $request->noHP,
    'role' => 'customer',
    'email_verified_at' => now(), // Auto-verify email since SMTP is not available
]);
```

**File:** `app/Http/Controllers/Api/AuthController.php` (line 36)

---

## Tugas E: Implementasi Logging Terdistribusi

### ✅ Log Context

Setiap log entry memiliki context lengkap yang ditambahkan oleh `CorrelationIdProcessor`:

- `correlation_id`: ID untuk melacak request di seluruh service
- `request_id`: Sama dengan correlation_id
- `user_id`: ID user yang melakukan request (jika authenticated)
- `user_email`: Email user (jika authenticated)
- `ip_address`: IP address client
- `user_agent`: User agent browser/client
- `method`: HTTP method (GET, POST, etc.)
- `url`: Full URL request

**File:** `app/Logging/CorrelationIdProcessor.php`

### ✅ Logging Format Konsisten

Semua log menggunakan format JSON yang konsisten melalui `JsonFormatter`:

```json
{
  "timestamp": "2024-12-15 14:30:15.123456",
  "level": "INFO",
  "message": "User registration attempt",
  "context": {
    "email": "user@example.com",
    "correlation_id": "550e8400-e29b-41d4-a716-446655440000"
  },
  "correlation_id": "550e8400-e29b-41d4-a716-446655440000",
  "request_id": "550e8400-e29b-41d4-a716-446655440000",
  "user_id": null,
  "user_email": null,
  "ip_address": "127.0.0.1",
  "user_agent": "PostmanRuntime/7.32.3",
  "method": "POST",
  "url": "http://localhost:8000/api/register",
  "channel": "json"
}
```

**File:** `app/Logging/JsonFormatter.php`

### ✅ Correlation ID Middleware

Middleware `CorrelationIdMiddleware` telah diimplementasikan dan terdaftar di semua route (API dan Web):

- Menerima Correlation ID dari header `X-Correlation-ID` jika ada
- Generate Correlation ID baru (UUID) jika tidak ada
- Menambahkan Correlation ID ke response header
- Menyimpan Correlation ID di request untuk digunakan di logging

**File:** 
- `app/Http/Middleware/CorrelationIdMiddleware.php`
- `bootstrap/app.php` (line 14-19)

### ✅ Proof of Distributed Tracing

Dokumentasi lengkap dengan contoh log tersedia di `DISTRIBUTED_TRACING.md`.

#### Contoh Tracing User Registration Request

Request dengan Correlation ID: `abc123-def456-ghi789`

**1. Request Masuk (Middleware)**
```json
{
  "timestamp": "2024-12-15 14:30:15.123456",
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

**2. User Berhasil Dibuat (Controller)**
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

#### Cara Melacak Request dengan Correlation ID

```bash
# Mencari semua log dengan correlation ID tertentu
grep "abc123-def456-ghi789" storage/logs/laravel.json

# Atau menggunakan jq untuk format yang lebih rapi
cat storage/logs/laravel.json | jq 'select(.correlation_id == "abc123-def456-ghi789")'
```

### ✅ Konfigurasi Logging

Logging dikonfigurasi di `config/logging.php`:

- Channel `json` menggunakan `JsonFormatter` dan `CorrelationIdProcessor`
- Log disimpan di `storage/logs/laravel.json`
- Format JSON konsisten untuk semua log entries

**File:** `config/logging.php` (line 70-79)

---

## File-File yang Terlibat

### Tugas A (Autentikasi)
- `app/Http/Controllers/Api/AuthController.php` - Controller utama
- `app/Http/Requests/Api/RegisterRequest.php` - Validasi register
- `app/Http/Requests/Api/LoginRequest.php` - Validasi login
- `app/Http/Requests/Api/UpdateUserRequest.php` - Validasi update profile
- `routes/api.php` - Route definitions
- `tests/Unit/AuthControllerTest.php` - Unit tests
- `tests/Feature/Api/AuthApiTest.php` - Feature tests

### Tugas E (Distributed Logging)
- `app/Http/Middleware/CorrelationIdMiddleware.php` - Middleware untuk Correlation ID
- `app/Logging/CorrelationIdProcessor.php` - Processor untuk menambahkan context ke log
- `app/Logging/JsonFormatter.php` - Formatter untuk format JSON konsisten
- `config/logging.php` - Konfigurasi logging
- `bootstrap/app.php` - Registrasi middleware
- `DISTRIBUTED_TRACING.md` - Dokumentasi lengkap

---

## Testing

### Menjalankan Unit Tests

```bash
php artisan test --filter AuthControllerTest
```

### Menjalankan Feature Tests

```bash
php artisan test --filter AuthApiTest
```

### Menjalankan Semua Tests

```bash
php artisan test
```

---

## Kesimpulan

✅ **Tugas A** telah selesai diimplementasikan dengan:
- Semua endpoint required (register, login, profile, CRUD)
- Validasi input lengkap
- Error handling konsisten
- Unit test minimal 1 (dan feature tests)

✅ **Tugas E** telah selesai diimplementasikan dengan:
- Log context lengkap
- Logging format konsisten (JSON)
- Proof of distributed tracing (dokumentasi dengan contoh log)

✅ **Auto-verification email** telah diimplementasikan di endpoint register karena tidak ada SMTP API.

