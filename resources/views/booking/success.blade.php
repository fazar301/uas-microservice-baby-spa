<x-user-dashboard>
    <div class="max-w-2xl mx-auto">
        <div class="bg-white rounded-xl shadow-sm p-8 text-center">
            <!-- Success Icon -->
            <div class="mx-auto flex items-center justify-center h-16 w-16 rounded-full bg-green-100 mb-4">
                <svg class="h-8 w-8 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                </svg>
            </div>

            <h1 class="text-2xl font-bold mb-2">Booking Berhasil!</h1>
            <p class="text-gray-600 mb-6">Booking Anda telah berhasil dibuat melalui Booking Service.</p>

            @if(isset($correlationId))
                <div class="mb-6 p-4 bg-blue-50 border border-blue-200 rounded-lg text-left">
                    <p class="text-xs text-blue-600">
                        <strong>Correlation ID:</strong> <code>{{ $correlationId }}</code>
                    </p>
                    <p class="text-xs text-blue-600 mt-2">
                        Gunakan Correlation ID ini untuk melacak log di semua service (User Service, Reservation Service, dan Booking Service).
                    </p>
                </div>
            @endif

            @if(isset($booking))
                <div class="bg-gray-50 rounded-lg p-6 mb-6 text-left">
                    <h3 class="font-semibold mb-4">Detail Booking</h3>
                    <div class="space-y-2 text-sm">
                        <div class="flex justify-between">
                            <span class="text-gray-600">Booking ID:</span>
                            <span class="font-medium">{{ $booking['reservation']['id'] ?? 'N/A' }}</span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-gray-600">Service:</span>
                            <span class="font-medium">
                                {{ $booking['reservation']['type'] === 'layanan' 
                                    ? ($booking['reservation']['layanan']['nama_layanan'] ?? 'Layanan') 
                                    : ($booking['reservation']['paket_layanan']['nama_paket'] ?? 'Paket') }}
                            </span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-gray-600">Tanggal:</span>
                            <span class="font-medium">
                                {{ isset($booking['reservation']['tanggal_reservasi']) 
                                    ? \Carbon\Carbon::parse($booking['reservation']['tanggal_reservasi'])->format('d F Y') 
                                    : 'N/A' }}
                            </span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-gray-600">Harga:</span>
                            <span class="font-medium">Rp {{ number_format($booking['reservation']['harga'] ?? 0, 0, ',', '.') }}</span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-gray-600">Status:</span>
                            <span class="font-medium">{{ ucfirst($booking['reservation']['status'] ?? 'pending') }}</span>
                        </div>
                    </div>
                </div>
            @endif

            <div class="flex justify-center gap-4">
                <a href="{{ route('booking.index') }}" class="px-6 py-2 bg-babypink-500 hover:bg-babypink-600 text-white rounded-md">
                    Lihat Daftar Booking
                </a>
                <a href="{{ route('dashboard') }}" class="px-6 py-2 border border-gray-300 rounded-md text-gray-700 hover:bg-gray-50">
                    Kembali ke Dashboard
                </a>
            </div>
        </div>
    </div>
</x-user-dashboard>

