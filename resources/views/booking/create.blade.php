<x-main-layout>
<div class="bg-pink-50 py-10">
    <div class="max-w-4xl mx-auto px-4">
        <!-- Info Box -->
        <div class="mb-6 p-4 bg-blue-50 border border-blue-200 rounded-lg">
            <p class="text-sm text-blue-800">
                <strong>Info:</strong> Form ini menggunakan Booking Service (Task C) yang akan memanggil User Service dan Reservation Service dengan Correlation ID untuk distributed tracing.
            </p>
            @if(isset($correlationId))
                <p class="text-xs text-blue-600 mt-2">Correlation ID: <code>{{ $correlationId }}</code></p>
            @endif
        </div>

        <div class="bg-white rounded-lg shadow-lg p-8 mb-12">
            <h2 class="text-2xl font-bold mb-6">Buat Booking</h2>
            
            @if($errors->any())
                <div class="mb-4 p-4 bg-red-50 border border-red-200 rounded-lg">
                    <ul class="text-sm text-red-600">
                        @foreach($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            @if(session('error'))
                <div class="mb-4 p-4 bg-red-50 border border-red-200 rounded-lg">
                    <p class="text-sm text-red-600">{{ session('error') }}</p>
                </div>
            @endif

            <form action="{{ route('booking.store') }}" method="POST" id="booking-form">
                @csrf
                <input type="hidden" name="correlation_id" value="{{ $correlationId ?? '' }}">
                <input type="hidden" name="type" value="{{ $type }}">
                <input type="hidden" name="service_id" value="{{ $service->id }}">

                <!-- Service Info -->
                <div class="mb-6 p-4 bg-gray-50 rounded-lg">
                    <h3 class="font-semibold mb-2">
                        {{ $type === 'layanan' ? $service->nama_layanan : $service->nama_paket }}
                    </h3>
                    <p class="text-lg font-bold text-babypink-600">
                        Rp {{ number_format($type === 'layanan' ? $service->harga_layanan : $service->harga_paket, 0, ',', '.') }}
                    </p>
                </div>

                <!-- Date Selection -->
                <div class="mb-6">
                    <label for="tanggal_reservasi" class="block text-sm font-medium text-gray-700 mb-2">
                        Pilih Tanggal <span class="text-red-500">*</span>
                    </label>
                    <select name="tanggal_reservasi" id="tanggal_reservasi" required class="w-full rounded-md border-gray-300 shadow-sm focus:border-pink-500 focus:ring-pink-500">
                        <option value="">Pilih Tanggal</option>
                        @foreach($availableDates as $date)
                            <option value="{{ $date['value'] }}">{{ $date['display'] }}</option>
                        @endforeach
                    </select>
                </div>

                <!-- Session Selection -->
                <div class="mb-6">
                    <label for="sesi_id" class="block text-sm font-medium text-gray-700 mb-2">
                        Pilih Sesi <span class="text-red-500">*</span>
                    </label>
                    <select name="sesi_id" id="sesi_id" required class="w-full rounded-md border-gray-300 shadow-sm focus:border-pink-500 focus:ring-pink-500">
                        <option value="">Pilih Sesi</option>
                        @foreach($sesis as $sesi)
                            <option value="{{ $sesi->id }}">{{ $sesi->jam }}</option>
                        @endforeach
                    </select>
                </div>

                <!-- Baby Selection -->
                <div class="mb-6">
                    <label class="block text-sm font-medium text-gray-700 mb-2">
                        Pilih Bayi
                    </label>
                    @if($bayis->count() > 0)
                        <select name="baby_id" class="w-full rounded-md border-gray-300 shadow-sm focus:border-pink-500 focus:ring-pink-500">
                            <option value="">Pilih Bayi yang Sudah Terdaftar</option>
                            @foreach($bayis as $bayi)
                                <option value="{{ $bayi->id }}">{{ $bayi->nama }} ({{ \Carbon\Carbon::parse($bayi->tanggal_lahir)->age }} tahun)</option>
                            @endforeach
                        </select>
                        <p class="text-xs text-gray-500 mt-2">Atau isi data bayi baru di bawah</p>
                    @endif
                </div>

                <!-- New Baby Data (Optional) -->
                <div class="mb-6 p-4 bg-gray-50 rounded-lg">
                    <h4 class="font-medium mb-3">Data Bayi Baru (Opsional)</h4>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label for="baby_data[nama]" class="block text-sm font-medium text-gray-700 mb-1">Nama Bayi</label>
                            <input type="text" name="baby_data[nama]" id="baby_data[nama]" class="w-full rounded-md border-gray-300 shadow-sm focus:border-pink-500 focus:ring-pink-500">
                        </div>
                        <div>
                            <label for="baby_data[tanggal_lahir]" class="block text-sm font-medium text-gray-700 mb-1">Tanggal Lahir</label>
                            <input type="date" name="baby_data[tanggal_lahir]" id="baby_data[tanggal_lahir]" class="w-full rounded-md border-gray-300 shadow-sm focus:border-pink-500 focus:ring-pink-500">
                        </div>
                        <div>
                            <label for="baby_data[jenis_kelamin]" class="block text-sm font-medium text-gray-700 mb-1">Jenis Kelamin</label>
                            <select name="baby_data[jenis_kelamin]" id="baby_data[jenis_kelamin]" class="w-full rounded-md border-gray-300 shadow-sm focus:border-pink-500 focus:ring-pink-500">
                                <option value="">Pilih</option>
                                <option value="Laki-laki">Laki-laki</option>
                                <option value="Perempuan">Perempuan</option>
                            </select>
                        </div>
                        <div>
                            <label for="baby_data[berat_lahir]" class="block text-sm font-medium text-gray-700 mb-1">Berat Lahir (kg)</label>
                            <input type="number" step="0.1" name="baby_data[berat_lahir]" id="baby_data[berat_lahir]" class="w-full rounded-md border-gray-300 shadow-sm focus:border-pink-500 focus:ring-pink-500">
                        </div>
                    </div>
                </div>

                <!-- Notes -->
                <div class="mb-6">
                    <label for="catatan" class="block text-sm font-medium text-gray-700 mb-2">Catatan (Opsional)</label>
                    <textarea name="catatan" id="catatan" rows="3" class="w-full rounded-md border-gray-300 shadow-sm focus:border-pink-500 focus:ring-pink-500"></textarea>
                </div>

                <!-- Submit Button -->
                <div class="flex justify-end gap-4">
                    <a href="{{ route('layanan.index') }}" class="px-6 py-2 border border-gray-300 rounded-md text-gray-700 hover:bg-gray-50">
                        Batal
                    </a>
                    <button type="submit" class="px-6 py-2 bg-babypink-500 hover:bg-babypink-600 text-white rounded-md">
                        Buat Booking
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
</x-main-layout>

