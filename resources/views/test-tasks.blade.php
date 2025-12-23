<x-user-dashboard>
    <div class="max-w-6xl mx-auto">
        <div class="mb-6">
            <h1 class="text-3xl font-bold mb-2">Test All Tasks (A-E)</h1>
            <p class="text-gray-600">Halaman ini untuk testing semua task melalui web interface</p>
        </div>

        <!-- Correlation ID Info -->
        <div class="mb-6 p-4 bg-blue-50 border border-blue-200 rounded-lg">
            <p class="text-sm text-blue-800">
                <strong>Correlation ID:</strong> Setiap request akan memiliki Correlation ID yang sama untuk distributed tracing. 
                Cek log untuk melihat Correlation ID yang digunakan.
            </p>
        </div>

        <!-- Task A: Authentication -->
        <div class="bg-white rounded-xl shadow-sm p-6 mb-6">
            <h2 class="text-xl font-semibold mb-4">Task A: Authentication & User Management</h2>
            <div class="space-y-3">
                <div class="p-4 bg-gray-50 rounded-lg">
                    <h3 class="font-medium mb-2">✅ Register</h3>
                    <p class="text-sm text-gray-600 mb-2">Endpoint: <code>/register</code></p>
                    <a href="{{ route('register') }}" class="inline-block px-4 py-2 bg-babypink-500 hover:bg-babypink-600 text-white rounded-md text-sm">
                        Test Register
                    </a>
                </div>
                <div class="p-4 bg-gray-50 rounded-lg">
                    <h3 class="font-medium mb-2">✅ Login</h3>
                    <p class="text-sm text-gray-600 mb-2">Endpoint: <code>/login</code></p>
                    <a href="{{ route('login') }}" class="inline-block px-4 py-2 bg-babypink-500 hover:bg-babypink-600 text-white rounded-md text-sm">
                        Test Login
                    </a>
                </div>
                <div class="p-4 bg-gray-50 rounded-lg">
                    <h3 class="font-medium mb-2">✅ User Profile</h3>
                    <p class="text-sm text-gray-600 mb-2">Endpoint: <code>/profile</code></p>
                    <a href="{{ route('profile.edit') }}" class="inline-block px-4 py-2 bg-babypink-500 hover:bg-babypink-600 text-white rounded-md text-sm">
                        Test Profile
                    </a>
                </div>
                <div class="p-4 bg-gray-50 rounded-lg">
                    <h3 class="font-medium mb-2">✅ User CRUD (Admin Only)</h3>
                    <p class="text-sm text-gray-600 mb-2">Endpoint: <code>/admin/users</code> atau Filament Panel</p>
                    @if(auth()->check() && auth()->user()->isAdmin())
                        <a href="/admin/pengguna" class="inline-block px-4 py-2 bg-babypink-500 hover:bg-babypink-600 text-white rounded-md text-sm">
                            Test User CRUD (Filament)
                        </a>
                    @else
                        <p class="text-sm text-gray-500">Login sebagai admin untuk mengakses</p>
                    @endif
                </div>
            </div>
        </div>

        <!-- Task B: Reservation Service -->
        <div class="bg-white rounded-xl shadow-sm p-6 mb-6">
            <h2 class="text-xl font-semibold mb-4">Task B: Reservation Service</h2>
            <div class="space-y-3">
                <div class="p-4 bg-gray-50 rounded-lg">
                    <h3 class="font-medium mb-2">✅ Create Reservation</h3>
                    <p class="text-sm text-gray-600 mb-2">Endpoint: <code>/reservasi/create/{type}/{slug}</code></p>
                    <p class="text-sm text-gray-500 mb-2">Atau gunakan form reservasi di dashboard</p>
                    <a href="{{ route('reservasi.index') }}" class="inline-block px-4 py-2 bg-babypink-500 hover:bg-babypink-600 text-white rounded-md text-sm">
                        Test Reservation
                    </a>
                </div>
                <div class="p-4 bg-gray-50 rounded-lg">
                    <h3 class="font-medium mb-2">✅ List Reservations</h3>
                    <p class="text-sm text-gray-600 mb-2">Endpoint: <code>/reservasi</code></p>
                    <a href="{{ route('reservasi.index') }}" class="inline-block px-4 py-2 bg-babypink-500 hover:bg-babypink-600 text-white rounded-md text-sm">
                        View Reservations
                    </a>
                </div>
                <div class="p-4 bg-blue-50 border border-blue-200 rounded-lg">
                    <p class="text-xs text-blue-800">
                        <strong>Note:</strong> Reservation Service menggunakan API endpoint <code>/api/reservations</code> 
                        dengan logging Correlation ID. Semua action di-log dengan correlation ID.
                    </p>
                </div>
            </div>
        </div>

        <!-- Task C: Booking Service -->
        <div class="bg-white rounded-xl shadow-sm p-6 mb-6">
            <h2 class="text-xl font-semibold mb-4">Task C: Booking Service</h2>
            <div class="space-y-3">
                <div class="p-4 bg-gray-50 rounded-lg">
                    <h3 class="font-medium mb-2">✅ Create Booking</h3>
                    <p class="text-sm text-gray-600 mb-2">Endpoint: <code>/booking/create/{type}/{slug}</code></p>
                    <p class="text-sm text-gray-500 mb-2">Booking Service akan call User Service dan Reservation Service</p>
                    <a href="{{ route('layanan.index') }}" class="inline-block px-4 py-2 bg-babypink-500 hover:bg-babypink-600 text-white rounded-md text-sm">
                        Pilih Layanan untuk Booking
                    </a>
                </div>
                <div class="p-4 bg-gray-50 rounded-lg">
                    <h3 class="font-medium mb-2">✅ List Bookings</h3>
                    <p class="text-sm text-gray-600 mb-2">Endpoint: <code>/booking</code></p>
                    <a href="{{ route('booking.index') }}" class="inline-block px-4 py-2 bg-babypink-500 hover:bg-babypink-600 text-white rounded-md text-sm">
                        View Bookings
                    </a>
                </div>
                <div class="p-4 bg-blue-50 border border-blue-200 rounded-lg">
                    <p class="text-xs text-blue-800">
                        <strong>Note:</strong> Booking Service memanggil User Service dan Reservation Service 
                        dengan Correlation ID dan Authorization token yang sama. Semua service calls di-log dengan correlation ID.
                    </p>
                </div>
            </div>
        </div>

        <!-- Task D: Correlation ID Middleware -->
        <div class="bg-white rounded-xl shadow-sm p-6 mb-6">
            <h2 class="text-xl font-semibold mb-4">Task D: Correlation ID Middleware</h2>
            <div class="p-4 bg-gray-50 rounded-lg">
                <h3 class="font-medium mb-2">✅ Middleware Applied</h3>
                <p class="text-sm text-gray-600 mb-2">
                    Correlation ID Middleware sudah diterapkan di semua route (web dan API).
                </p>
                <ul class="text-sm text-gray-600 list-disc list-inside space-y-1">
                    <li>Middleware: <code>CorrelationIdMiddleware</code></li>
                    <li>Applied to: All routes (web & API)</li>
                    <li>Header: <code>X-Correlation-ID</code></li>
                </ul>
            </div>
        </div>

        <!-- Task E: Distributed Logging -->
        <div class="bg-white rounded-xl shadow-sm p-6 mb-6">
            <h2 class="text-xl font-semibold mb-4">Task E: Distributed Logging</h2>
            <div class="space-y-3">
                <div class="p-4 bg-gray-50 rounded-lg">
                    <h3 class="font-medium mb-2">✅ Log Context</h3>
                    <p class="text-sm text-gray-600 mb-2">Semua log memiliki context: user_id, correlation_id, action</p>
                </div>
                <div class="p-4 bg-gray-50 rounded-lg">
                    <h3 class="font-medium mb-2">✅ Consistent Format</h3>
                    <p class="text-sm text-gray-600 mb-2">Format: JSON dengan CorrelationIdProcessor dan JsonFormatter</p>
                </div>
                <div class="p-4 bg-gray-50 rounded-lg">
                    <h3 class="font-medium mb-2">✅ Distributed Tracing</h3>
                    <p class="text-sm text-gray-600 mb-2">Semua service menggunakan Correlation ID yang sama untuk tracing</p>
                    <p class="text-xs text-gray-500 mt-2">
                        Cek file log di <code>storage/logs/laravel.log</code> untuk melihat Correlation ID yang sama 
                        di semua service calls.
                    </p>
                </div>
            </div>
        </div>

        <!-- How to Test -->
        <div class="bg-yellow-50 border border-yellow-200 rounded-xl p-6">
            <h2 class="text-xl font-semibold mb-4">Cara Test</h2>
            <ol class="list-decimal list-inside space-y-2 text-sm text-gray-700">
                <li>Login atau Register untuk test Task A</li>
                <li>Buat Reservation untuk test Task B (menggunakan Reservation Service)</li>
                <li>Buat Booking untuk test Task C (menggunakan Booking Service yang call User & Reservation Service)</li>
                <li>Cek log di <code>storage/logs/laravel.log</code> untuk melihat Correlation ID yang sama di semua service</li>
                <li>Semua action akan ter-log dengan format JSON yang konsisten</li>
            </ol>
        </div>
    </div>
</x-user-dashboard>

