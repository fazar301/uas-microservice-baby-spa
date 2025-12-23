<x-user-dashboard>
    <div class="max-w-4xl mx-auto">
        <div class="flex items-center justify-between mb-6">
            <h1 class="text-2xl font-bold">Detail Booking</h1>
            <a href="{{ route('booking.index') }}" class="text-babypink-600 hover:text-babypink-700">
                ← Kembali ke Daftar
            </a>
        </div>

        @if(isset($correlationId))
            <div class="mb-6 p-4 bg-blue-50 border border-blue-200 rounded-lg">
                <p class="text-xs text-blue-600">
                    <strong>Correlation ID:</strong> <code>{{ $correlationId }}</code>
                </p>
            </div>
        @endif

        @if(isset($booking))
            <div class="bg-white rounded-xl shadow-sm p-8">
                <!-- Header -->
                <div class="border-b pb-6 mb-6">
                    <div class="flex items-center justify-between mb-4">
                        <h2 class="text-xl font-semibold">
                            {{ $booking['reservation']['type'] === 'layanan' 
                                ? ($booking['reservation']['layanan']['nama_layanan'] ?? 'Layanan') 
                                : ($booking['reservation']['paket_layanan']['nama_paket'] ?? 'Paket') }}
                        </h2>
                        <span class="px-3 py-1 text-sm rounded-full {{ 
                            ($booking['reservation']['status'] ?? 'pending') === 'confirmed' 
                                ? 'bg-babypink-100 text-babypink-600' 
                                : 'bg-orange-100 text-orange-600' 
                        }}">
                            {{ ucfirst($booking['reservation']['status'] ?? 'pending') }}
                        </span>
                    </div>
                    <p class="text-sm text-gray-500">Booking ID: {{ $booking['reservation']['id'] ?? 'N/A' }}</p>
                </div>

                <!-- User Information -->
                <div class="mb-6">
                    <h3 class="font-semibold mb-3">Informasi User</h3>
                    <div class="bg-gray-50 rounded-lg p-4 space-y-2">
                        <div class="flex justify-between">
                            <span class="text-gray-600">Nama:</span>
                            <span class="font-medium">{{ $booking['user']['name'] ?? 'N/A' }}</span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-gray-600">Email:</span>
                            <span class="font-medium">{{ $booking['user']['email'] ?? 'N/A' }}</span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-gray-600">No. HP:</span>
                            <span class="font-medium">{{ $booking['user']['noHP'] ?? 'N/A' }}</span>
                        </div>
                    </div>
                </div>

                <!-- Reservation Information -->
                <div class="mb-6">
                    <h3 class="font-semibold mb-3">Informasi Reservasi</h3>
                    <div class="bg-gray-50 rounded-lg p-4 space-y-2">
                        <div class="flex justify-between">
                            <span class="text-gray-600">Tanggal:</span>
                            <span class="font-medium">
                                {{ isset($booking['reservation']['tanggal_reservasi']) 
                                    ? \Carbon\Carbon::parse($booking['reservation']['tanggal_reservasi'])->format('d F Y') 
                                    : 'N/A' }}
                            </span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-gray-600">Sesi:</span>
                            <span class="font-medium">{{ $booking['reservation']['sesi']['jam'] ?? 'N/A' }}</span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-gray-600">Harga:</span>
                            <span class="font-medium">Rp {{ number_format($booking['reservation']['harga'] ?? 0, 0, ',', '.') }}</span>
                        </div>
                        @if(isset($booking['reservation']['catatan']) && $booking['reservation']['catatan'])
                            <div class="flex justify-between">
                                <span class="text-gray-600">Catatan:</span>
                                <span class="font-medium">{{ $booking['reservation']['catatan'] }}</span>
                            </div>
                        @endif
                    </div>
                </div>

                <!-- Baby Information -->
                @if(isset($booking['reservation']['bayi']))
                    <div class="mb-6">
                        <h3 class="font-semibold mb-3">Informasi Bayi</h3>
                        <div class="bg-gray-50 rounded-lg p-4 space-y-2">
                            <div class="flex justify-between">
                                <span class="text-gray-600">Nama:</span>
                                <span class="font-medium">{{ $booking['reservation']['bayi']['nama'] ?? 'N/A' }}</span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-gray-600">Tanggal Lahir:</span>
                                <span class="font-medium">
                                    {{ isset($booking['reservation']['bayi']['tanggal_lahir']) 
                                        ? \Carbon\Carbon::parse($booking['reservation']['bayi']['tanggal_lahir'])->format('d F Y') 
                                        : 'N/A' }}
                                </span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-gray-600">Jenis Kelamin:</span>
                                <span class="font-medium">{{ $booking['reservation']['bayi']['jenis_kelamin'] ?? 'N/A' }}</span>
                            </div>
                        </div>
                    </div>
                @endif

                <!-- Service Information -->
                <div class="p-4 bg-blue-50 border border-blue-200 rounded-lg">
                    <p class="text-xs text-blue-800">
                        <strong>Info:</strong> Data ini diambil dari Booking Service yang memanggil User Service dan Reservation Service dengan Correlation ID yang sama untuk distributed tracing.
                    </p>
                </div>
            </div>
        @else
            <div class="bg-white rounded-xl shadow-sm p-8 text-center">
                <p class="text-gray-500">Booking tidak ditemukan</p>
            </div>
        @endif
    </div>
</x-user-dashboard>

