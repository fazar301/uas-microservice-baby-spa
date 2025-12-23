<?php

namespace App\Http\Controllers;

use Validator;
use App\Models\Bayi;
use App\Models\Sesi;
use App\Models\Layanan;
use App\Models\Reservation;
use App\Models\PaketLayanan;
use Illuminate\Http\Request;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;

class ReservationController extends Controller
{
    public function create($type, $slug)
    {
        if ($type === 'layanan') {
            $service = Layanan::where('slug', $slug)->firstOrFail();
        } else {
            $service = PaketLayanan::where('slug', $slug)->firstOrFail();
        }

        if (!$service) {
            return redirect()->route('layanan.index')->with('error', 'Layanan tidak ditemukan.');
        }

        $bayis = Bayi::where('user_id', Auth::user()->id)
            ->where('is_temporary', false)
            ->get();

        // Get all sessions
        $allSesis = Sesi::orderBy('jam')->get();
        
        // Get today's date
        $today = now()->format('Y-m-d');
        
        // Get all reservations for the next 7 days
        $reservations = Reservation::where('tanggal_reservasi', '>=', $today)
            ->where('tanggal_reservasi', '<=', now()->addDays(7)->format('Y-m-d'))
            ->get()
            ->groupBy(function($reservation) {
                return $reservation->tanggal_reservasi . '_' . $reservation->sesi_id;
            });

        // Get holidays for the next 7 days
        $holidays = \App\Models\Holiday::where(function($query) use ($today) {
            $query->where('tanggal_mulai', '<=', now()->addDays(7)->format('Y-m-d'))
                  ->where('tanggal_selesai', '>=', $today);
        })->get();

        // Generate available dates
        $availableDates = [];
        for ($i = 0; $i < 7; $i++) {
            $date = now()->addDays($i);
            $dateStr = $date->format('Y-m-d');
            $displayDate = $date->locale('id')->isoFormat('dddd, D MMMM YYYY');
            
            // Check if date is not a holiday
            $isHoliday = false;
            foreach ($holidays as $holiday) {
                if ($date->between($holiday->tanggal_mulai, $holiday->tanggal_selesai)) {
                    $isHoliday = true;
                    break;
                }
            }
            
            if (!$isHoliday) {
                $availableDates[] = [
                    'value' => $dateStr,
                    'display' => $displayDate
                ];
            }
        }

        // Filter out sessions that are already booked
        $sesis = $allSesis->filter(function($sesi) use ($reservations) {
            // For each date in the next 7 days
            for ($i = 0; $i < 7; $i++) {
                $date = now()->addDays($i)->format('Y-m-d');
                $key = $date . '_' . $sesi->id;
                
                // If this session is not booked for this date, it's available
                if (!isset($reservations[$key])) {
                    return true;
                }
            }
            return false;
        });

        return view('front.reservasi-form', compact('service', 'type', 'sesis', 'bayis', 'availableDates'));
    }

    public function store(Request $request)
    {
        $correlationId = $request->get('correlation_id') ?? $request->header('X-Correlation-ID');
        
        try {
            Log::info('Reservation creation attempt via web', [
                'user_id' => Auth::id(),
                'correlation_id' => $correlationId,
            ]);
            
            DB::beginTransaction();

            // Check if the selected date is a holiday
            $isHoliday = \App\Models\Holiday::where(function ($query) use ($request) {
                $query->where('tanggal_mulai', '<=', $request->tanggal_reservasi)
                      ->where('tanggal_selesai', '>=', $request->tanggal_reservasi);
            })->exists();

            if ($isHoliday) {
                if ($request->ajax() || $request->wantsJson()) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Tanggal yang dipilih merupakan hari libur. Silakan pilih tanggal lain.'
                    ], 422);
                }
                return back()->withInput()->with('error', 'Tanggal yang dipilih merupakan hari libur. Silakan pilih tanggal lain.');
            }

            // Calculate baby's age
            $tanggal_lahir = \Carbon\Carbon::parse($request->tanggal_lahir);
            $umur_bayi = $tanggal_lahir->age;
            $umur_bayi_bulan = (int) $tanggal_lahir->diffInMonths(now());

            // Get service and validate age
            if ($request->type === 'layanan') {
                $service = Layanan::with('kategori')->findOrFail($request->service_id);
            } else {
                $service = PaketLayanan::with('kategori')->findOrFail($request->service_id);
            }

            // Validate age based on service category
            if ($service->kategori) {
                $is_valid_age = false;
                switch ($service->kategori->nama_kategori) {
                    case 'Baby':
                        if ($umur_bayi_bulan <= 12) {
                            $is_valid_age = true;
                        }
                        break;
                    case 'Kids':
                        if ($umur_bayi >= 1 && $umur_bayi <= 3) {
                            $is_valid_age = true;
                        }
                        break;
                    case 'Children':
                        if ($umur_bayi >= 3) {
                            $is_valid_age = true;
                        }
                        break;
                }

                if (!$is_valid_age) {
                    if ($request->ajax() || $request->wantsJson()) {
                        return response()->json([
                            'success' => false,
                            'message' => 'Umur bayi tidak sesuai dengan persyaratan layanan yang dipilih.'
                        ], 422);
                    }
                    return back()->withInput()->with('error', 'Umur bayi tidak sesuai dengan persyaratan layanan yang dipilih.');
                }
            }

            $validator = Validator::make($request->all(), [
                'type' => 'required|in:layanan,paket',
                'service_id' => 'required',
                'sesi_id' => 'required|exists:sesis,id',
                'nama_bayi' => 'required|string|max:255',
                'tanggal_lahir' => 'required|date|before_or_equal:today',
                'jenis_kelamin' => 'required|in:laki-laki,perempuan',
                'berat_lahir' => 'required|numeric|min:0.5|max:6',
                'berat_sekarang' => 'required|numeric|min:0.5|max:20',
                'save_baby_data' => 'nullable|boolean',
                'terms' => 'required|accepted',
                'parent_name' => 'required|string|max:255',
                'noHP' => 'required|string|max:20|unique:users,noHP,' . Auth::id(),
            ], [
                'type.required' => 'Tipe layanan harus dipilih.',
                'type.in' => 'Tipe layanan tidak valid.',
                'service_id.required' => 'Layanan harus dipilih.',
                'tanggal_reservasi.required' => 'Tanggal reservasi harus diisi.',
                'tanggal_reservasi.after' => 'Tanggal reservasi harus setelah hari ini.',
                'sesi_id.required' => 'Sesi harus dipilih.',
                'sesi_id.exists' => 'Sesi tidak valid.',
                'nama_bayi.required' => 'Nama bayi harus diisi.',
                'tanggal_lahir.required' => 'Tanggal lahir bayi harus diisi.',
                'tanggal_lahir.before_or_equal' => 'Tanggal lahir tidak boleh lebih dari hari ini.',
                'jenis_kelamin.required' => 'Jenis kelamin harus dipilih.',
                'jenis_kelamin.in' => 'Jenis kelamin tidak valid.',
                'berat_lahir.required' => 'Berat lahir harus diisi.',
                'berat_lahir.numeric' => 'Berat lahir harus berupa angka.',
                'berat_lahir.min' => 'Berat lahir minimal 0.5 kg.',
                'berat_lahir.max' => 'Berat lahir maksimal 6 kg.',
                'berat_sekarang.required' => 'Berat sekarang harus diisi.',
                'berat_sekarang.numeric' => 'Berat sekarang harus berupa angka.',
                'berat_sekarang.min' => 'Berat sekarang minimal 0.5 kg.',
                'berat_sekarang.max' => 'Berat sekarang maksimal 20 kg.',
                'terms.required' => 'Anda harus menyetujui syarat dan ketentuan.',
                'terms.accepted' => 'Anda harus menyetujui syarat dan ketentuan.',
                'parent_name.required' => 'Nama orang tua harus diisi.',
                'noHP.required' => 'Nomor telepon harus diisi.',
                'noHP.unique' => 'Nomor telepon ini sudah digunakan oleh pengguna lain.',
            ]);

            if ($validator->fails()) {
                if ($request->ajax() || $request->wantsJson()) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Validasi gagal',
                        'errors' => $validator->errors()
                    ], 422);
                }
                return back()->withErrors($validator)->withInput();
            }

            if ($request->type === 'layanan') {
                $service = Layanan::findOrFail($request->service_id);
                $harga = $service->harga_layanan;
            } else {
                $service = PaketLayanan::findOrFail($request->service_id);
                $harga = $service->harga_paket;
            }

            $sesi = Sesi::findOrFail($request->sesi_id);

            // Update user's noHP if it is null
            $user = Auth::user();
            if (is_null($user->noHP)) {
                $user->noHP = $request->noHP;
                $user->save();
            }

            $reservation = new Reservation();
            $reservation->user_id = Auth::user()->id;
            if(Auth::user()->name != $request->parent_name){
                $user = \App\Models\User::find(Auth::user()->id);
                $user->name = $request->parent_name;
                $user->save();
            }
            $reservation->layanan_id = $request->service_id;
            $reservation->type = $request->type;
            $reservation->sesi_id = $request->sesi_id;
            $reservation->tanggal_reservasi = $request->tanggal_reservasi;
            $reservation->status = 'pending';
            $reservation->harga = $harga;
            $reservation->catatan = $request->catatan;

            // Handle baby data based on selection
            if ($request->baby_data_option === 'existing' && $request->existing_baby) {
                // Use existing baby
                $bayi = Bayi::findOrFail($request->existing_baby);
                $reservation->bayi_id = $bayi->id;
            } else {
                // Create new baby record
                $bayi = new Bayi();
                if ($request->save_baby_data) {
                    $bayi->user_id = Auth::user()->id;
                } else {
                    // Set a temporary user_id for the reservation
                    $bayi->user_id = Auth::user()->id;
                    $bayi->is_temporary = true;
                }
                $bayi->nama = $request->nama_bayi;
                $bayi->tanggal_lahir = $request->tanggal_lahir;
                $bayi->jenis_kelamin = $request->jenis_kelamin;
                $bayi->berat_lahir = $request->berat_lahir;
                $bayi->berat_sekarang = $request->berat_sekarang;
                $bayi->save();

                $reservation->bayi_id = $bayi->id;
            }
            $reservation->save();

            // Create a default transaction with status pending and method midtrans
            $transaksi = new \App\Models\Transaksi();
            $transaksi->reservasi_id = $reservation->id;
            $transaksi->tanggal = \Carbon\Carbon::now();
            $transaksi->jumlah = $harga; // Amount from reservation price
            $transaksi->discount_amount = 0; // Default discount is 0
            $transaksi->status = 'pending'; // Initial status is pending
            $transaksi->metode = 'midtrans'; // Default method
            $transaksi->save();

            DB::commit();

            Log::info('Reservation created successfully via web', [
                'user_id' => Auth::id(),
                'reservation_id' => $reservation->id,
                'correlation_id' => $correlationId,
            ]);

            // Store necessary data in session for payment page
            session([
                'reservation_id' => $reservation->id,
                'service_name' => $service->nama_layanan ?? $service->nama_paket,
                'service_price' => $harga,
                'parent_name' => Auth::user()->name,
                'baby_name' => $request->nama_bayi,
                'phone' => Auth::user()->noHP,
                'email' => Auth::user()->email
            ]);

            if ($request->ajax() || $request->wantsJson()) {
                return response()->json([
                    'success' => true,
                    'redirect' => route('payment.show', $reservation)
                ]);
            }
            return redirect()->route('payment.show', $reservation);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Reservation creation failed via web', [
                'user_id' => Auth::id(),
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'correlation_id' => $correlationId,
            ]);
            if ($request->ajax() || $request->wantsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Terjadi kesalahan saat membuat reservasi.'
                ], 400);
            }
            return back()->withInput()->with('error', 'Terjadi kesalahan saat membuat reservasi. Silakan coba lagi.');
        }
    }

    public function success()
    {
        // Retrieve reservation ID from session
        $reservationId = Session::get('reservation_id');

        $reservation = null;
        if ($reservationId) {
            // Fetch the reservation with necessary relationships
            $reservation = Reservation::with(['user', 'layanan', 'paketLayanan', 'bayi'])->find($reservationId);

            // Optionally, clear the reservation_id from session after retrieving
            // Session::forget('reservation_id');
        }

        // Pass the reservation object to the view
        return view('front.reservasi-success', compact('reservation'));
    }

    public function downloadInvoice(Reservation $reservation)
    {
        // Load any related data needed for the invoice
        $reservation->load(['user', 'layanan', 'paketLayanan', 'bayi', 'transaksi']);

        // Get the payment data
        $payment = $reservation->transaksi;

        // Format the data for the template
        $reservation->parent_name = $reservation->user->name;
        $reservation->email = $reservation->user->email;
        $reservation->phone = $reservation->user->noHP;
        $reservation->baby_name = $reservation->bayi->nama;
        $reservation->baby_age_formatted = $reservation->bayi->tanggal_lahir->age . ' tahun ' . (int) $reservation->bayi->tanggal_lahir->diffInMonths(now()) % 12 . ' bulan';
        $reservation->baby_birth_weight = $reservation->bayi->berat_lahir;
        $reservation->baby_current_weight = $reservation->bayi->berat_sekarang;
        $reservation->service_name = $reservation->type === 'layanan' ? $reservation->layanan->nama_layanan : $reservation->paketLayanan->nama_paket;
        $reservation->service_price = $reservation->harga;
        $reservation->formatted_appointment_date = \Carbon\Carbon::parse($reservation->tanggal_reservasi)->locale('id')->isoFormat('dddd, D MMMM Y');
        $reservation->appointment_time = $reservation->sesi->jam;

        // Create a dedicated Blade view for the invoice PDF
        $pdf = Pdf::loadView('templates.invoice', compact('reservation', 'payment'));

        // Download the PDF
        return $pdf->download('invoice_' . $reservation->kode . '.pdf');
    }

    public function showPayment(Reservation $reservation)
    {
        // Check if the reservation belongs to the authenticated user
        if ($reservation->user_id !== Auth::id()) {
            return redirect()->route('dashboard')->with('error', 'Anda tidak memiliki akses ke reservasi ini.');
        }

        return view('front.pembayaran', compact('reservation'));
    }
    
    public function pending()
    {
        return view('front.pending-payment', [
            'title' => 'Pembayaran Pending'
        ]);
    }

    public function index()
    {
        $reservations = Reservation::where('user_id', Auth::id())
            ->with(['layanan', 'paketLayanan', 'bayi', 'sesi', 'ulasan'])
            ->orderBy('created_at', 'desc')
            ->get();
        $layanans = Layanan::all();
        $paketLayanans = PaketLayanan::all();
        return view('dashboard_user.reservasi', [
            'reservations' => $reservations,
            'layanans' => $layanans,
            'paketLayanans' => $paketLayanans,
            'title' => 'Daftar Reservasi'
        ]);
    }

    public function getAvailableSessions(Request $request)
    {
        $date = $request->date;
        
        // Check if the date is a holiday
        $isHoliday = \App\Models\Holiday::where(function ($query) use ($date) {
            $query->where('tanggal_mulai', '<=', $date)
                  ->where('tanggal_selesai', '>=', $date);
        })->exists();

        if ($isHoliday) {
            return response()->json([
                'success' => false,
                'message' => 'Tanggal yang dipilih merupakan hari libur. Silakan pilih tanggal lain.'
            ], 422);
        }

        // Get all sessions
        $allSesis = Sesi::orderBy('jam')->get();
        
        // Get active reservations for the specific date (excluding cancelled ones)
        $bookedSessions = Reservation::where('tanggal_reservasi', $date)
            ->whereNotIn('status', ['cancelled'])
            ->pluck('sesi_id')
            ->toArray();
        
        // Check if the date is a Friday
        $isFriday = \Carbon\Carbon::parse($date)->dayOfWeek === 5; // 5 represents Friday
        
        // Filter out booked sessions and sessions before 14:00 on Fridays
        $availableSessions = $allSesis->filter(function($sesi) use ($bookedSessions, $isFriday) {
            // If it's Friday, only allow sessions after 14:00
            if ($isFriday) {
                $sessionTime = \Carbon\Carbon::createFromFormat('H:i:s', $sesi->jam);
                if ($sessionTime->format('H:i') < '14:00') {
                    return false;
                }
            }
            return !in_array($sesi->id, $bookedSessions);
        })->values();

        return response()->json([
            'success' => true,
            'available_sessions' => $availableSessions->pluck('id')->toArray()
        ]);
    }

    public function getMinimumAge(Request $request){
        try {
            $request->validate([
                'idLayanan' => 'required',
                'type' => 'required|in:layanan,paket',
                'tanggal_lahir' => 'required|date|before_or_equal:today'
            ]);

            // Get service based on type
            if ($request->type === 'layanan') {
                $service = Layanan::with('kategori')->findOrFail($request->idLayanan);
            } else {
                $service = PaketLayanan::with('kategori')->findOrFail($request->idLayanan);
            }

            // Calculate baby's age
            $tanggal_lahir = \Carbon\Carbon::parse($request->tanggal_lahir);
            $umur_bayi = $tanggal_lahir->age;
            $umur_bayi_bulan = (int) $tanggal_lahir->diffInMonths(now());

            // Check age requirement based on service category
            $is_valid_age = false;
            $error_message = '';
            $min_age = '';
            $max_age = '';

            if ($service->kategori) {
                switch ($service->kategori->nama_kategori) {
                    case 'Baby':
                        $min_age = '0 bulan';
                        $max_age = '12 bulan';
                        if ($umur_bayi_bulan <= 12) {
                            $is_valid_age = true;
                        } else {
                            $error_message = 'Layanan ini hanya untuk anak usia 0-12 bulan. <br>Usia anak Anda: ' . $umur_bayi . ' tahun ' . ($umur_bayi_bulan % 12) . ' bulan.';
                        }
                        break;
                    case 'Kids':
                        $min_age = '1 tahun';
                        $max_age = '3 tahun';
                        if ($umur_bayi >= 1 && $umur_bayi <= 3) {
                            $is_valid_age = true;
                        } else {
                            $error_message = 'Layanan ini hanya untuk anak usia 1-3 tahun. <br>Usia anak Anda: ' . $umur_bayi . ' tahun ' . ($umur_bayi_bulan % 12) . ' bulan.';
                        }
                        break;
                    case 'Children':
                        $min_age = '3 tahun';
                        $max_age = 'Tidak ada batas maksimal';
                        if ($umur_bayi >= 3) {
                            $is_valid_age = true;
                        } else {
                            $error_message = 'Layanan ini hanya untuk anak usia 3 tahun ke atas. <br>Usia anak Anda: ' . $umur_bayi . ' tahun ' . ($umur_bayi_bulan % 12) . ' bulan.';
                        }
                        break;
                    default:
                        $is_valid_age = true; // No age restriction
                        break;
                }
            } else {
                $is_valid_age = true; // No category means no age restriction
            }

            return response()->json([
                'success' => true,
                'is_valid_age' => $is_valid_age,
                'error_message' => $error_message,
                'baby_age_years' => $umur_bayi,
                'baby_age_months' => $umur_bayi_bulan,
                'service_category' => $service->kategori ? $service->kategori->nama_kategori : 'Tidak ada kategori',
                'min_age' => $min_age,
                'max_age' => $max_age
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Terjadi kesalahan saat memvalidasi usia: ' . $e->getMessage()
            ], 500);
        }
    }
} 