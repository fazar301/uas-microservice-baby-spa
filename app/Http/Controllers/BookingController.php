<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;
use App\Models\Layanan;
use App\Models\PaketLayanan;
use App\Models\Sesi;
use App\Models\Bayi;

/**
 * Web Controller for Booking Service (Task C)
 * 
 * This controller provides web interface for booking service
 * which calls User Service and Reservation Service
 */
class BookingController extends Controller
{
    /**
     * Show the booking form
     */
    public function create($type, $slug)
    {
        $correlationId = request()->get('correlation_id') ?? request()->header('X-Correlation-ID');
        
        try {
            Log::info('Booking form access attempt', [
                'user_id' => Auth::id(),
                'type' => $type,
                'slug' => $slug,
                'correlation_id' => $correlationId,
            ]);

            if ($type === 'layanan') {
                $service = Layanan::where('slug', $slug)->firstOrFail();
            } else {
                $service = PaketLayanan::where('slug', $slug)->firstOrFail();
            }

            if (!$service) {
                return redirect()->route('layanan.index')
                    ->with('error', 'Layanan tidak ditemukan.');
            }

            $bayis = Bayi::where('user_id', Auth::user()->id)
                ->where('is_temporary', false)
                ->get();

            // Get all sessions
            $allSesis = Sesi::orderBy('jam')->get();
            
            // Get today's date
            $today = now()->format('Y-m-d');
            
            // Get all reservations for the next 7 days
            $reservations = \App\Models\Reservation::where('tanggal_reservasi', '>=', $today)
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
                for ($i = 0; $i < 7; $i++) {
                    $date = now()->addDays($i)->format('Y-m-d');
                    $key = $date . '_' . $sesi->id;
                    
                    if (!isset($reservations[$key])) {
                        return true;
                    }
                }
                return false;
            });

            Log::info('Booking form displayed successfully', [
                'user_id' => Auth::id(),
                'service_id' => $service->id,
                'correlation_id' => $correlationId,
            ]);

            return view('booking.create', compact('service', 'type', 'sesis', 'bayis', 'availableDates', 'correlationId'));
        } catch (\Exception $e) {
            Log::error('Failed to display booking form', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'correlation_id' => $correlationId,
            ]);

            return redirect()->route('layanan.index')
                ->with('error', 'Gagal memuat form booking. Silakan coba lagi.');
        }
    }

    /**
     * Store a new booking via Booking Service (calls User Service + Reservation Service)
     */
    public function store(Request $request)
    {
        $correlationId = $request->get('correlation_id') ?? $request->header('X-Correlation-ID');
        
        try {
            Log::info('Booking creation attempt via web', [
                'user_id' => Auth::id(),
                'correlation_id' => $correlationId,
            ]);

            // Validate required fields
            $request->validate([
                'service_id' => 'required|integer',
                'type' => 'required|string|in:layanan,paket',
                'sesi_id' => 'required|integer',
                'tanggal_reservasi' => 'required|date|after_or_equal:today',
            ], [
                'service_id.required' => 'Service ID wajib diisi.',
                'type.required' => 'Tipe service wajib diisi.',
                'type.in' => 'Tipe service harus layanan atau paket.',
                'sesi_id.required' => 'Sesi wajib diisi.',
                'tanggal_reservasi.required' => 'Tanggal reservasi wajib diisi.',
                'tanggal_reservasi.after_or_equal' => 'Tanggal reservasi harus hari ini atau setelahnya.',
            ]);

            $user = Auth::user();
            $baseUrl = config('app.url', 'http://127.0.0.1:8000');
            $authToken = $user->createToken('web-booking')->plainTextToken;

            // Prepare booking data
            $bookingData = $request->only([
                'service_id', 'type', 'sesi_id', 'tanggal_reservasi', 
                'catatan', 'baby_id'
            ]);

            // Handle baby data if provided
            if ($request->filled('baby_data')) {
                $bookingData['baby_data'] = $request->baby_data;
            }

            // Call Booking Service API
            $response = Http::withHeaders([
                'X-Correlation-ID' => $correlationId,
                'Authorization' => "Bearer {$authToken}",
                'Accept' => 'application/json',
            ])->timeout(5)->post("{$baseUrl}/api/bookings", $bookingData);

            if ($response->successful()) {
                $result = $response->json();
                
                Log::info('Booking created successfully via web', [
                    'user_id' => $user->id,
                    'booking_id' => $result['data']['reservation']['id'] ?? null,
                    'correlation_id' => $correlationId,
                ]);

                // Revoke the temporary token
                $user->tokens()->where('name', 'web-booking')->delete();

                return redirect()->route('booking.success')
                    ->with('success', 'Booking berhasil dibuat!')
                    ->with('booking', $result['data']);
            } else {
                $error = $response->json();
                
                Log::error('Booking creation failed via web', [
                    'user_id' => $user->id,
                    'error' => $error['message'] ?? 'Unknown error',
                    'correlation_id' => $correlationId,
                ]);

                // Revoke the temporary token
                $user->tokens()->where('name', 'web-booking')->delete();

                return back()
                    ->withInput()
                    ->with('error', $error['message'] ?? 'Gagal membuat booking. Silakan coba lagi.');
            }
        } catch (\Illuminate\Validation\ValidationException $e) {
            Log::warning('Booking validation failed', [
                'user_id' => Auth::id(),
                'errors' => $e->errors(),
                'correlation_id' => $correlationId,
            ]);

            return back()
                ->withErrors($e->errors())
                ->withInput();
        } catch (\Exception $e) {
            Log::error('Booking creation exception', [
                'user_id' => Auth::id(),
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'correlation_id' => $correlationId,
            ]);

            return back()
                ->withInput()
                ->with('error', 'Terjadi kesalahan saat membuat booking. Silakan coba lagi.');
        }
    }

    /**
     * Show booking success page
     */
    public function success()
    {
        $correlationId = request()->get('correlation_id') ?? request()->header('X-Correlation-ID');
        
        Log::info('Booking success page accessed', [
            'user_id' => Auth::id(),
            'correlation_id' => $correlationId,
        ]);

        $booking = session('booking');
        
        if (!$booking) {
            return redirect()->route('dashboard')
                ->with('error', 'Data booking tidak ditemukan.');
        }

        return view('booking.success', compact('booking', 'correlationId'));
    }

    /**
     * Show list of bookings
     */
    public function index(Request $request)
    {
        $correlationId = $request->get('correlation_id') ?? $request->header('X-Correlation-ID');
        
        try {
            Log::info('Booking list retrieval attempt via web', [
                'user_id' => Auth::id(),
                'correlation_id' => $correlationId,
            ]);

            $user = Auth::user();
            $baseUrl = config('app.url', 'http://127.0.0.1:8000');
            $authToken = $user->createToken('web-booking-list')->plainTextToken;

            // Call Booking Service API to get bookings
            $response = Http::withHeaders([
                'X-Correlation-ID' => $correlationId,
                'Authorization' => "Bearer {$authToken}",
                'Accept' => 'application/json',
            ])->timeout(5)->get("{$baseUrl}/api/bookings");

            // Revoke the temporary token
            $user->tokens()->where('name', 'web-booking-list')->delete();

            if ($response->successful()) {
                $result = $response->json();
                $bookings = $result['data'] ?? [];
                
                Log::info('Booking list retrieved successfully via web', [
                    'user_id' => $user->id,
                    'total_bookings' => count($bookings),
                    'correlation_id' => $correlationId,
                ]);

                return view('booking.index', compact('bookings', 'correlationId'));
            } else {
                Log::error('Failed to retrieve booking list via web', [
                    'user_id' => $user->id,
                    'correlation_id' => $correlationId,
                ]);

                return view('booking.index', ['bookings' => [], 'correlationId' => $correlationId])
                    ->with('error', 'Gagal memuat daftar booking.');
            }
        } catch (\Exception $e) {
            Log::error('Booking list retrieval exception', [
                'user_id' => Auth::id(),
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'correlation_id' => $correlationId,
            ]);

            return view('booking.index', ['bookings' => [], 'correlationId' => $correlationId])
                ->with('error', 'Terjadi kesalahan saat memuat daftar booking.');
        }
    }

    /**
     * Show single booking details
     */
    public function show($id)
    {
        $correlationId = request()->get('correlation_id') ?? request()->header('X-Correlation-ID');
        
        try {
            Log::info('Booking details retrieval attempt via web', [
                'user_id' => Auth::id(),
                'booking_id' => $id,
                'correlation_id' => $correlationId,
            ]);

            $user = Auth::user();
            $baseUrl = config('app.url', 'http://127.0.0.1:8000');
            $authToken = $user->createToken('web-booking-show')->plainTextToken;

            // Call Booking Service API to get booking details
            $response = Http::withHeaders([
                'X-Correlation-ID' => $correlationId,
                'Authorization' => "Bearer {$authToken}",
                'Accept' => 'application/json',
            ])->timeout(5)->get("{$baseUrl}/api/bookings/{$id}");

            // Revoke the temporary token
            $user->tokens()->where('name', 'web-booking-show')->delete();

            if ($response->successful()) {
                $result = $response->json();
                $booking = $result['data'] ?? null;
                
                if (!$booking) {
                    return redirect()->route('booking.index')
                        ->with('error', 'Booking tidak ditemukan.');
                }

                Log::info('Booking details retrieved successfully via web', [
                    'user_id' => $user->id,
                    'booking_id' => $id,
                    'correlation_id' => $correlationId,
                ]);

                return view('booking.show', compact('booking', 'correlationId'));
            } else {
                Log::error('Failed to retrieve booking details via web', [
                    'user_id' => $user->id,
                    'booking_id' => $id,
                    'correlation_id' => $correlationId,
                ]);

                return redirect()->route('booking.index')
                    ->with('error', 'Gagal memuat detail booking.');
            }
        } catch (\Exception $e) {
            Log::error('Booking details retrieval exception', [
                'user_id' => Auth::id(),
                'booking_id' => $id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'correlation_id' => $correlationId,
            ]);

            return redirect()->route('booking.index')
                ->with('error', 'Terjadi kesalahan saat memuat detail booking.');
        }
    }
}

