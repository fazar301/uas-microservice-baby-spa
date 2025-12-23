<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\CreateReservationRequest;
use App\Http\Requests\Api\UpdateReservationRequest;
use App\Models\Reservation;
use App\Models\Layanan;
use App\Models\PaketLayanan;
use App\Models\Sesi;
use App\Models\Bayi;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

class ReservationApiController extends Controller
{
    /**
     * Display a listing of reservations.
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $user = $request->user();

            Log::info('Reservation list retrieval attempt', [
                'user_id' => $user->id,
                'correlation_id' => $request->get('correlation_id'),
            ]);

            $query = Reservation::with(['user', 'layanan', 'paketLayanan', 'sesi', 'bayi'])
                ->where('user_id', $user->id);

            // Filter by status if provided
            if ($request->filled('status')) {
                $query->where('status', $request->status);
            }

            // Filter by date if provided
            if ($request->filled('tanggal_reservasi')) {
                $query->whereDate('tanggal_reservasi', $request->tanggal_reservasi);
            }

            $reservations = $query->orderBy('tanggal_reservasi', 'desc')
                ->orderBy('created_at', 'desc')
                ->paginate(15);

            Log::info('Reservation list retrieved successfully', [
                'user_id' => $user->id,
                'total_reservations' => $reservations->total(),
                'correlation_id' => $request->get('correlation_id'),
            ]);

            return response()->json([
                'status' => 'success',
                'data' => $reservations,
            ], 200);
        } catch (\Exception $e) {
            Log::error('Failed to retrieve reservation list', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'correlation_id' => $request->get('correlation_id'),
            ]);

            return response()->json([
                'status' => 'error',
                'message' => 'Failed to retrieve reservations.',
                'error' => config('app.debug') ? $e->getMessage() : 'Internal server error',
            ], 500);
        }
    }

    /**
     * Store a newly created reservation.
     */
    public function store(CreateReservationRequest $request): JsonResponse
    {
        DB::beginTransaction();
        try {
            $user = $request->user();

            Log::info('Reservation creation attempt', [
                'user_id' => $user->id,
                'service_id' => $request->service_id,
                'type' => $request->type,
                'correlation_id' => $request->get('correlation_id'),
            ]);

            // Get service and calculate price
            if ($request->type === 'layanan') {
                $service = Layanan::findOrFail($request->service_id);
                $harga = $service->harga_layanan;
            } else {
                $service = PaketLayanan::findOrFail($request->service_id);
                $harga = $service->harga_paket;
            }

            // Verify session exists
            $sesi = Sesi::findOrFail($request->sesi_id);

            // Handle baby data
            $bayiId = null;
            if ($request->filled('baby_id')) {
                // Use existing baby
                $bayi = Bayi::where('id', $request->baby_id)
                    ->where('user_id', $user->id)
                    ->firstOrFail();
                $bayiId = $bayi->id;
            } elseif ($request->filled('baby_data')) {
                // Create new baby
                $bayi = new Bayi();
                $bayi->user_id = $user->id;
                $bayi->nama = $request->baby_data['nama'];
                $bayi->tanggal_lahir = $request->baby_data['tanggal_lahir'];
                $bayi->jenis_kelamin = $request->baby_data['jenis_kelamin'];
                $bayi->berat_lahir = $request->baby_data['berat_lahir'] ?? null;
                $bayi->berat_sekarang = $request->baby_data['berat_sekarang'] ?? null;
                $bayi->is_temporary = $request->baby_data['is_temporary'] ?? false;
                $bayi->save();
                $bayiId = $bayi->id;
            }

            // Create reservation
            $reservation = new Reservation();
            $reservation->user_id = $user->id;
            $reservation->layanan_id = $request->service_id;
            $reservation->type = $request->type;
            $reservation->sesi_id = $request->sesi_id;
            $reservation->bayi_id = $bayiId;
            $reservation->tanggal_reservasi = $request->tanggal_reservasi;
            $reservation->status = 'pending';
            $reservation->harga = $harga;
            $reservation->catatan = $request->catatan ?? null;
            $reservation->save();

            DB::commit();

            Log::info('Reservation created successfully', [
                'user_id' => $user->id,
                'reservation_id' => $reservation->id,
                'correlation_id' => $request->get('correlation_id'),
            ]);

            return response()->json([
                'status' => 'success',
                'message' => 'Reservation created successfully',
                'data' => [
                    'reservation' => $reservation->load(['user', 'layanan', 'paketLayanan', 'sesi', 'bayi']),
                ],
            ], 201);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            DB::rollBack();
            Log::warning('Reservation creation failed: Resource not found', [
                'error' => $e->getMessage(),
                'correlation_id' => $request->get('correlation_id'),
            ]);

            return response()->json([
                'status' => 'error',
                'message' => 'Service, session, or baby not found.',
            ], 404);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Reservation creation failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'correlation_id' => $request->get('correlation_id'),
            ]);

            return response()->json([
                'status' => 'error',
                'message' => 'Failed to create reservation.',
                'error' => config('app.debug') ? $e->getMessage() : 'Internal server error',
            ], 500);
        }
    }

    /**
     * Display the specified reservation.
     */
    public function show(Request $request, int $id): JsonResponse
    {
        try {
            $user = $request->user();

            Log::info('Reservation detail retrieval attempt', [
                'user_id' => $user->id,
                'reservation_id' => $id,
                'correlation_id' => $request->get('correlation_id'),
            ]);

            $reservation = Reservation::with(['user', 'layanan', 'paketLayanan', 'sesi', 'bayi', 'transaksi'])
                ->where('id', $id)
                ->where('user_id', $user->id)
                ->firstOrFail();

            Log::info('Reservation detail retrieved successfully', [
                'user_id' => $user->id,
                'reservation_id' => $id,
                'correlation_id' => $request->get('correlation_id'),
            ]);

            return response()->json([
                'status' => 'success',
                'data' => [
                    'reservation' => $reservation,
                ],
            ], 200);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            Log::warning('Reservation not found', [
                'reservation_id' => $id,
                'correlation_id' => $request->get('correlation_id'),
            ]);

            return response()->json([
                'status' => 'error',
                'message' => 'Reservation not found.',
            ], 404);
        } catch (\Exception $e) {
            Log::error('Failed to retrieve reservation detail', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'correlation_id' => $request->get('correlation_id'),
            ]);

            return response()->json([
                'status' => 'error',
                'message' => 'Failed to retrieve reservation.',
                'error' => config('app.debug') ? $e->getMessage() : 'Internal server error',
            ], 500);
        }
    }

    /**
     * Update the specified reservation.
     */
    public function update(UpdateReservationRequest $request, int $id): JsonResponse
    {
        DB::beginTransaction();
        try {
            $user = $request->user();

            Log::info('Reservation update attempt', [
                'user_id' => $user->id,
                'reservation_id' => $id,
                'correlation_id' => $request->get('correlation_id'),
            ]);

            $reservation = Reservation::where('id', $id)
                ->where('user_id', $user->id)
                ->firstOrFail();

            // Only allow update if status is pending
            if ($reservation->status !== 'pending') {
                Log::warning('Reservation update failed: Status is not pending', [
                    'user_id' => $user->id,
                    'reservation_id' => $id,
                    'current_status' => $reservation->status,
                    'correlation_id' => $request->get('correlation_id'),
                ]);

                return response()->json([
                    'status' => 'error',
                    'message' => 'Only pending reservations can be updated.',
                ], 400);
            }

            // Update fields
            if ($request->filled('sesi_id')) {
                $sesi = Sesi::findOrFail($request->sesi_id);
                $reservation->sesi_id = $request->sesi_id;
            }

            if ($request->filled('tanggal_reservasi')) {
                $reservation->tanggal_reservasi = $request->tanggal_reservasi;
            }

            if ($request->filled('catatan')) {
                $reservation->catatan = $request->catatan;
            }

            if ($request->filled('baby_id')) {
                $bayi = Bayi::where('id', $request->baby_id)
                    ->where('user_id', $user->id)
                    ->firstOrFail();
                $reservation->bayi_id = $bayi->id;
            }

            $reservation->save();

            DB::commit();

            Log::info('Reservation updated successfully', [
                'user_id' => $user->id,
                'reservation_id' => $id,
                'correlation_id' => $request->get('correlation_id'),
            ]);

            return response()->json([
                'status' => 'success',
                'message' => 'Reservation updated successfully',
                'data' => [
                    'reservation' => $reservation->load(['user', 'layanan', 'paketLayanan', 'sesi', 'bayi']),
                ],
            ], 200);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            DB::rollBack();
            Log::warning('Reservation update failed: Resource not found', [
                'error' => $e->getMessage(),
                'correlation_id' => $request->get('correlation_id'),
            ]);

            return response()->json([
                'status' => 'error',
                'message' => 'Reservation, session, or baby not found.',
            ], 404);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Reservation update failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'correlation_id' => $request->get('correlation_id'),
            ]);

            return response()->json([
                'status' => 'error',
                'message' => 'Failed to update reservation.',
                'error' => config('app.debug') ? $e->getMessage() : 'Internal server error',
            ], 500);
        }
    }

    /**
     * Remove the specified reservation.
     */
    public function destroy(Request $request, int $id): JsonResponse
    {
        DB::beginTransaction();
        try {
            $user = $request->user();

            Log::info('Reservation deletion attempt', [
                'user_id' => $user->id,
                'reservation_id' => $id,
                'correlation_id' => $request->get('correlation_id'),
            ]);

            $reservation = Reservation::where('id', $id)
                ->where('user_id', $user->id)
                ->firstOrFail();

            // Only allow delete if status is pending
            if ($reservation->status !== 'pending') {
                Log::warning('Reservation deletion failed: Status is not pending', [
                    'user_id' => $user->id,
                    'reservation_id' => $id,
                    'current_status' => $reservation->status,
                    'correlation_id' => $request->get('correlation_id'),
                ]);

                return response()->json([
                    'status' => 'error',
                    'message' => 'Only pending reservations can be deleted.',
                ], 400);
            }

            $reservation->delete();

            DB::commit();

            Log::info('Reservation deleted successfully', [
                'user_id' => $user->id,
                'reservation_id' => $id,
                'correlation_id' => $request->get('correlation_id'),
            ]);

            return response()->json([
                'status' => 'success',
                'message' => 'Reservation deleted successfully',
            ], 200);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            DB::rollBack();
            Log::warning('Reservation not found for deletion', [
                'reservation_id' => $id,
                'correlation_id' => $request->get('correlation_id'),
            ]);

            return response()->json([
                'status' => 'error',
                'message' => 'Reservation not found.',
            ], 404);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Reservation deletion failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'correlation_id' => $request->get('correlation_id'),
            ]);

            return response()->json([
                'status' => 'error',
                'message' => 'Failed to delete reservation.',
                'error' => config('app.debug') ? $e->getMessage() : 'Internal server error',
            ], 500);
        }
    }
}

