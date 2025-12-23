<x-user-dashboard>
    <div class="flex items-center justify-between mb-6">
        <h1 class="text-2xl font-bold md:mt-0">Daftar Booking</h1>
        <div class="flex items-center gap-4">
            <x-notification-button :count="3" />
            <x-profile-dropdown username="Akun Saya" />
        </div>
    </div>

    @if(session('error'))
        <div class="mb-4 p-4 bg-red-50 border border-red-200 rounded-lg">
            <p class="text-sm text-red-600">{{ session('error') }}</p>
        </div>
    @endif

    @if(session('success'))
        <div class="mb-4 p-4 bg-green-50 border border-green-200 rounded-lg">
            <p class="text-sm text-green-600">{{ session('success') }}</p>
        </div>
    @endif

    <!-- Info Box -->
    <div class="mb-6 p-4 bg-blue-50 border border-blue-200 rounded-lg">
        <p class="text-sm text-blue-800">
            <strong>Info:</strong> Halaman ini menggunakan Booking Service (Task C) yang memanggil User Service dan Reservation Service dengan Correlation ID untuk distributed tracing.
        </p>
        @if(isset($correlationId))
            <p class="text-xs text-blue-600 mt-2">Correlation ID: <code>{{ $correlationId }}</code></p>
        @endif
    </div>

    @if(empty($bookings))
        <div class="bg-white rounded-xl shadow-sm p-8 text-center">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-16 w-16 mx-auto text-gray-400 mb-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
            </svg>
            <p class="text-gray-500 mb-4">Belum ada booking</p>
            <a href="{{ route('layanan.index') }}" class="inline-block px-4 py-2 bg-babypink-500 hover:bg-babypink-600 text-white rounded-md">
                Buat Booking Baru
            </a>
        </div>
    @else
        <div class="space-y-4">
            @foreach($bookings as $booking)
                <div class="bg-white rounded-xl shadow-sm p-6">
                    <div class="flex items-center justify-between mb-4">
                        <div>
                            <h3 class="text-lg font-semibold">
                                {{ $booking['reservation']['type'] === 'layanan' 
                                    ? ($booking['reservation']['layanan']['nama_layanan'] ?? 'Layanan') 
                                    : ($booking['reservation']['paket_layanan']['nama_paket'] ?? 'Paket') }}
                            </h3>
                            <p class="text-sm text-gray-500">
                                Booking ID: {{ $booking['reservation']['id'] ?? 'N/A' }}
                            </p>
                        </div>
                        <span class="px-3 py-1 text-sm rounded-full {{ 
                            ($booking['reservation']['status'] ?? 'pending') === 'confirmed' 
                                ? 'bg-babypink-100 text-babypink-600' 
                                : 'bg-orange-100 text-orange-600' 
                        }}">
                            {{ ucfirst($booking['reservation']['status'] ?? 'pending') }}
                        </span>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                        <div class="space-y-2">
                            <div class="flex items-center text-sm">
                                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="mr-2 text-gray-500">
                                    <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path>
                                    <circle cx="12" cy="7" r="4"></circle>
                                </svg>
                                <span><strong>User:</strong> {{ $booking['user']['name'] ?? 'N/A' }}</span>
                            </div>
                            <div class="flex items-center text-sm">
                                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="mr-2 text-gray-500">
                                    <rect width="18" height="18" x="3" y="4" rx="2" ry="2"></rect>
                                    <line x1="16" x2="16" y1="2" y2="6"></line>
                                    <line x1="8" x2="8" y1="2" y2="6"></line>
                                    <line x1="3" x2="21" y1="10" y2="10"></line>
                                </svg>
                                <span><strong>Tanggal:</strong> {{ isset($booking['reservation']['tanggal_reservasi']) ? \Carbon\Carbon::parse($booking['reservation']['tanggal_reservasi'])->format('d F Y') : 'N/A' }}</span>
                            </div>
                        </div>
                        <div class="space-y-2">
                            <div class="flex items-center text-sm">
                                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="mr-2 text-gray-500">
                                    <circle cx="12" cy="12" r="10"></circle>
                                    <polyline points="12 6 12 12 16 14"></polyline>
                                </svg>
                                <span><strong>Harga:</strong> Rp {{ number_format($booking['reservation']['harga'] ?? 0, 0, ',', '.') }}</span>
                            </div>
                        </div>
                    </div>

                    <div class="flex justify-end mt-4">
                        <a href="{{ route('booking.show', $booking['reservation']['id'] ?? '') }}" class="px-4 py-2 bg-babypink-500 hover:bg-babypink-600 text-white rounded-md text-sm">
                            Lihat Detail
                        </a>
                    </div>
                </div>
            @endforeach
        </div>
    @endif
</x-user-dashboard>

