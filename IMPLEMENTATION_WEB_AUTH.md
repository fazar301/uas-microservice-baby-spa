# Implementasi Autentikasi Web - Tugas A

## Ringkasan
Implementasi autentikasi pada layanan user untuk website (bukan hanya API) dengan semua requirement yang diminta.

## Endpoint yang Diimplementasikan

### 1. Register (Web)
- **Route:** `POST /register`
- **Controller:** `App\Http\Controllers\Auth\RegisteredUserController`
- **View:** `resources/views/auth/register.blade.php`
- **Fitur:**
  - Validasi input lengkap (name, email, noHP, password)
  - Auto-verification email (email_verified_at = now())
  - Error handling dengan logging
  - Logging dengan correlation ID

### 2. Login (Web)
- **Route:** `POST /login`
- **Controller:** `App\Http\Controllers\Auth\AuthenticatedSessionController`
- **View:** `resources/views/auth/login.blade.php`
- **Fitur:**
  - Validasi input (email, password)
  - Error handling untuk invalid credentials
  - Logging dengan correlation ID
  - Redirect ke intended URL atau dashboard

### 3. User Profile (Web)
- **Route:** 
  - `GET /profile` - Tampilkan profile
  - `PATCH /profile` - Update profile
  - `DELETE /profile` - Hapus akun
- **Controller:** `App\Http\Controllers\ProfileController`
- **View:** `resources/views/dashboard_user/setting.blade.php`
- **Fitur:**
  - Tampilkan informasi user
  - Update profile (name, email)
  - Update password
  - Hapus akun dengan konfirmasi password
  - Error handling dengan logging
  - Logging dengan correlation ID

### 4. User CRUD untuk Admin (Web)
- **Route:**
  - `GET /admin/users` - List semua users
  - `GET /admin/users/{id}` - Detail user
  - `GET /admin/users/{id}/edit` - Form edit user
  - `PUT /admin/users/{id}` - Update user
  - `DELETE /admin/users/{id}` - Hapus user
- **Controller:** `App\Http\Controllers\UserController`
- **Views:**
  - `resources/views/admin/users/index.blade.php` - List users
  - `resources/views/admin/users/show.blade.php` - Detail user
  - `resources/views/admin/users/edit.blade.php` - Edit user
- **Fitur:**
  - Hanya admin yang bisa akses
  - List users dengan pagination
  - Detail user lengkap
  - Edit user (name, email, noHP, role)
  - Hapus user (tidak bisa hapus akun sendiri)
  - Validasi input lengkap
  - Error handling dengan logging
  - Logging dengan correlation ID

## Validasi Input

### Register
- name: required, string, max:255
- email: required, email, unique
- noHP: required, string, max:20, unique
- password: required, confirmed, min:8
- Custom error messages dalam Bahasa Indonesia

### Login
- email: required, email
- password: required, string

### Profile Update
- name: required, string, max:255
- email: required, email, unique (except current user)

### Admin User Update
- name: required, string, max:255, min:3
- email: required, email, unique (except target user)
- noHP: nullable, string, max:20, unique (except target user)
- role: required, in:admin,customer
- Custom error messages dalam Bahasa Indonesia

## Error Handling

Semua endpoint memiliki error handling yang konsisten dengan:
- Try-catch blocks
- Logging error dengan correlation ID
- User-friendly error messages
- Redirect dengan error messages untuk web

Contoh error handling:
```php
try {
    // Business logic
    Log::info('Action success', [
        'user_id' => $user->id,
        'correlation_id' => $request->get('correlation_id'),
    ]);
    return redirect()->with('success', 'Message');
} catch (\Exception $e) {
    Log::error('Action failed', [
        'error' => $e->getMessage(),
        'trace' => $e->getTraceAsString(),
        'correlation_id' => $request->get('correlation_id'),
    ]);
    return redirect()->back()->with('error', 'Error message');
}
```

## Logging Terdistribusi

Semua action di-log dengan correlation ID:

### Login
- Login attempt (dengan email)
- Login success (dengan user_id)
- Login failed (invalid credentials)

### Register
- Registration attempt (dengan email)
- Registration success (dengan user_id)
- Registration validation failed

### Profile
- Profile page accessed
- Profile update attempt
- Profile update success
- Email changed (verification reset)
- Account deletion attempt
- Account deletion success

### Admin User Management
- User list retrieved
- User details retrieved
- User edit form accessed
- User update attempt
- User update success
- User update validation failed
- User deletion attempt
- User deletion success
- Unauthorized access attempts

Semua log menggunakan format JSON konsisten dengan:
- `correlation_id`: ID untuk melacak request
- `user_id`: ID user yang melakukan action
- `timestamp`: Waktu log
- `level`: Level log (INFO, WARNING, ERROR)
- `message`: Pesan log
- `context`: Context tambahan

## File-File yang Dibuat/Diperbaiki

### Controllers
- `app/Http/Controllers/UserController.php` - **BARU** - Controller untuk admin user management
- `app/Http/Controllers/ProfileController.php` - **DIPERBAIKI** - Ditambahkan logging
- `app/Http/Controllers/Auth/AuthenticatedSessionController.php` - **DIPERBAIKI** - Ditambahkan logging
- `app/Http/Controllers/Auth/RegisteredUserController.php` - **DIPERBAIKI** - Ditambahkan logging

### Views
- `resources/views/admin/users/index.blade.php` - **BARU** - List users untuk admin
- `resources/views/admin/users/show.blade.php` - **BARU** - Detail user untuk admin
- `resources/views/admin/users/edit.blade.php` - **BARU** - Edit user untuk admin
- `resources/views/dashboard_user/setting.blade.php` - **SUDAH ADA** - Profile page

### Routes
- `routes/web.php` - **DIPERBAIKI** - Ditambahkan routes untuk admin user management

### Layout
- `resources/views/layouts/user-dashboard.blade.php` - **DIPERBAIKI** - Ditambahkan menu "Manajemen Pengguna" untuk admin

## Cara Menggunakan

### Untuk User Biasa
1. **Register:** Kunjungi `/register` dan isi form
2. **Login:** Kunjungi `/login` dan masukkan credentials
3. **Profile:** Setelah login, klik "Pengaturan" di sidebar untuk mengakses profile
4. **Update Profile:** Edit informasi di halaman profile
5. **Hapus Akun:** Gunakan form hapus akun di halaman profile

### Untuk Admin
1. **Login sebagai admin:** Gunakan `admin@latumi.com` / `admin123`
2. **Akses User Management:** Klik "Manajemen Pengguna" di sidebar
3. **List Users:** Lihat semua users dengan pagination
4. **Detail User:** Klik "Lihat" untuk melihat detail user
5. **Edit User:** Klik "Edit" untuk mengubah informasi user
6. **Hapus User:** Klik "Hapus" untuk menghapus user (tidak bisa hapus akun sendiri)

## Logging Examples

### Login Success
```json
{
  "timestamp": "2024-12-18 10:30:15.123456",
  "level": "INFO",
  "message": "User logged in successfully",
  "context": {},
  "correlation_id": "550e8400-e29b-41d4-a716-446655440000",
  "user_id": 1,
  "user_email": "admin@latumi.com",
  "ip_address": "127.0.0.1",
  "method": "POST",
  "url": "http://localhost:8000/login",
  "channel": "json"
}
```

### User Update by Admin
```json
{
  "timestamp": "2024-12-18 10:35:20.456789",
  "level": "INFO",
  "message": "User updated successfully",
  "context": {
    "user_id": 1,
    "target_user_id": 5,
    "correlation_id": "550e8400-e29b-41d4-a716-446655440001"
  },
  "correlation_id": "550e8400-e29b-41d4-a716-446655440001",
  "user_id": 1,
  "user_email": "admin@latumi.com",
  "ip_address": "127.0.0.1",
  "method": "PUT",
  "url": "http://localhost:8000/admin/users/5",
  "channel": "json"
}
```

### Unauthorized Access Attempt
```json
{
  "timestamp": "2024-12-18 10:40:10.789012",
  "level": "WARNING",
  "message": "Unauthorized access attempt to user list",
  "context": {
    "user_id": 2,
    "correlation_id": "550e8400-e29b-41d4-a716-446655440002"
  },
  "correlation_id": "550e8400-e29b-41d4-a716-446655440002",
  "user_id": 2,
  "user_email": "customer@latumi.com",
  "ip_address": "127.0.0.1",
  "method": "GET",
  "url": "http://localhost:8000/admin/users",
  "channel": "json"
}
```

## Testing

Semua endpoint sudah memiliki:
- ✅ Validasi input
- ✅ Error handling
- ✅ Logging dengan correlation ID
- ✅ Unit test (minimal 1) - sudah ada di `tests/Unit/AuthControllerTest.php`
- ✅ Feature test - sudah ada di `tests/Feature/Api/AuthApiTest.php`

## Kesimpulan

✅ **Semua requirement tugas A telah terpenuhi:**
- ✅ Endpoint register, login, user profile, user CRUD (tanpa create)
- ✅ Validasi input lengkap
- ✅ Error handling konsisten
- ✅ Unit test minimal 1
- ✅ Logging terdistribusi dengan correlation ID
- ✅ Implementasi di website (bukan hanya API)

✅ **Semua action di-log dengan:**
- ✅ Log context lengkap
- ✅ Logging format konsisten (JSON)
- ✅ Correlation ID untuk distributed tracing

