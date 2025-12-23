<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Service C: Booking Service
 * 
 * Service ini melakukan call ke:
 * 1. User Service (untuk mendapatkan informasi user)
 * 2. Reservation Service (untuk membuat reservasi)
 * 
 * Dengan ketentuan:
 * - Mengirim dan menerima Correlation ID
 * - Meneruskan Authorization token
 * - Error handling konsisten untuk kegagalan service lain
 */
class BookingServiceController extends Controller
{
    /**
     * Create a booking with user verification and reservation creation
     * 
     * This endpoint:
     * 1. Calls User Service to verify user exists and get user details
     * 2. Calls Reservation Service to create reservation
     * 3. Returns combined result
     */
    public function createBooking(Request $request): JsonResponse
    {
        $correlationId = $request->get('correlation_id') ?? $request->header('X-Correlation-ID');
        $authToken = $request->bearerToken() ?? $request->header('Authorization');

        try {
            Log::info('Booking creation attempt', [
                'correlation_id' => $correlationId,
                'user_id' => $request->user()->id ?? null,
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

            $user = $request->user();
            if (!$user) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Unauthorized. Please login first.',
                ], 401);
            }

            // Step 1: Call User Service to verify and get user details
            $userServiceResponse = $this->callUserService($user->id, $correlationId, $authToken);
            
            if (!$userServiceResponse['success']) {
                Log::error('User service call failed', [
                    'correlation_id' => $correlationId,
                    'error' => $userServiceResponse['error'],
                ]);

                return response()->json([
                    'status' => 'error',
                    'message' => 'Failed to verify user. Please try again.',
                    'service_error' => 'user_service',
                    'error' => config('app.debug') ? $userServiceResponse['error'] : 'User service unavailable',
                ], 503);
            }

            $userData = $userServiceResponse['data'];

            // Step 2: Call Reservation Service to create reservation
            $reservationData = $request->only([
                'service_id', 'type', 'sesi_id', 'tanggal_reservasi', 
                'catatan', 'baby_id', 'baby_data'
            ]);

            $reservationServiceResponse = $this->callReservationService(
                $reservationData,
                $correlationId,
                $authToken
            );

            if (!$reservationServiceResponse['success']) {
                Log::error('Reservation service call failed', [
                    'correlation_id' => $correlationId,
                    'error' => $reservationServiceResponse['error'],
                ]);

                return response()->json([
                    'status' => 'error',
                    'message' => 'Failed to create reservation. Please try again.',
                    'service_error' => 'reservation_service',
                    'error' => config('app.debug') ? $reservationServiceResponse['error'] : 'Reservation service unavailable',
                ], 503);
            }

            $reservationData = $reservationServiceResponse['data'];

            Log::info('Booking created successfully', [
                'correlation_id' => $correlationId,
                'user_id' => $user->id,
                'reservation_id' => $reservationData['reservation']['id'] ?? null,
            ]);

            return response()->json([
                'status' => 'success',
                'message' => 'Booking created successfully',
                'data' => [
                    'user' => $userData['user'] ?? null,
                    'reservation' => $reservationData['reservation'] ?? null,
                ],
            ], 201);
        } catch (\Illuminate\Validation\ValidationException $e) {
            Log::warning('Booking creation validation failed', [
                'correlation_id' => $correlationId,
                'errors' => $e->errors(),
            ]);

            return response()->json([
                'status' => 'error',
                'message' => 'Validation failed',
                'errors' => $e->errors(),
            ], 422);
        } catch (\Exception $e) {
            Log::error('Booking creation failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'correlation_id' => $correlationId,
            ]);

            return response()->json([
                'status' => 'error',
                'message' => 'Failed to create booking. Please try again.',
                'error' => config('app.debug') ? $e->getMessage() : 'Internal server error',
            ], 500);
        }
    }

    /**
     * Get list of bookings for the authenticated user
     */
    public function index(Request $request): JsonResponse
    {
        $correlationId = $request->get('correlation_id') ?? $request->header('X-Correlation-ID');
        $authToken = $request->bearerToken() ?? $request->header('Authorization');

        try {
            Log::info('Booking list retrieval attempt', [
                'user_id' => $request->user()->id ?? null,
                'correlation_id' => $correlationId,
            ]);

            $user = $request->user();
            if (!$user) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Unauthorized. Please login first.',
                ], 401);
            }

            // Call Reservation Service to get all reservations
            $reservationServiceResponse = $this->callReservationServiceForList($correlationId, $authToken);

            if (!$reservationServiceResponse['success']) {
                Log::error('Reservation service call failed for booking list', [
                    'correlation_id' => $correlationId,
                    'error' => $reservationServiceResponse['error'],
                ]);

                return response()->json([
                    'status' => 'error',
                    'message' => 'Failed to retrieve bookings.',
                    'service_error' => 'reservation_service',
                    'error' => config('app.debug') ? $reservationServiceResponse['error'] : 'Reservation service unavailable',
                ], 503);
            }

            $reservations = $reservationServiceResponse['data']['data'] ?? [];

            // For each reservation, get user info
            $bookings = [];
            foreach ($reservations as $reservation) {
                $userServiceResponse = $this->callUserService($user->id, $correlationId, $authToken);
                
                if ($userServiceResponse['success']) {
                    $bookings[] = [
                        'user' => $userServiceResponse['data']['user'] ?? null,
                        'reservation' => $reservation,
                    ];
                }
            }

            Log::info('Booking list retrieved successfully', [
                'user_id' => $user->id,
                'total_bookings' => count($bookings),
                'correlation_id' => $correlationId,
            ]);

            return response()->json([
                'status' => 'success',
                'data' => $bookings,
            ], 200);
        } catch (\Exception $e) {
            Log::error('Failed to retrieve booking list', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'correlation_id' => $correlationId,
            ]);

            return response()->json([
                'status' => 'error',
                'message' => 'Failed to retrieve bookings.',
                'error' => config('app.debug') ? $e->getMessage() : 'Internal server error',
            ], 500);
        }
    }

    /**
     * Get booking details with user and reservation information
     */
    public function getBooking(Request $request, int $bookingId): JsonResponse
    {
        $correlationId = $request->get('correlation_id') ?? $request->header('X-Correlation-ID');
        $authToken = $request->bearerToken() ?? $request->header('Authorization');

        try {
            Log::info('Booking detail retrieval attempt', [
                'correlation_id' => $correlationId,
                'booking_id' => $bookingId,
                'user_id' => $request->user()->id ?? null,
            ]);

            $user = $request->user();
            if (!$user) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Unauthorized. Please login first.',
                ], 401);
            }

            // Call User Service
            $userServiceResponse = $this->callUserService($user->id, $correlationId, $authToken);
            
            if (!$userServiceResponse['success']) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Failed to get user information.',
                    'service_error' => 'user_service',
                ], 503);
            }

            // Call Reservation Service
            $reservationServiceResponse = $this->callReservationServiceById(
                $bookingId,
                $correlationId,
                $authToken
            );

            if (!$reservationServiceResponse['success']) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Failed to get reservation information.',
                    'service_error' => 'reservation_service',
                ], 503);
            }

            Log::info('Booking detail retrieved successfully', [
                'correlation_id' => $correlationId,
                'booking_id' => $bookingId,
            ]);

            return response()->json([
                'status' => 'success',
                'data' => [
                    'user' => $userServiceResponse['data']['user'] ?? null,
                    'reservation' => $reservationServiceResponse['data']['reservation'] ?? null,
                ],
            ], 200);
        } catch (\Exception $e) {
            Log::error('Failed to retrieve booking detail', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'correlation_id' => $correlationId,
            ]);

            return response()->json([
                'status' => 'error',
                'message' => 'Failed to retrieve booking.',
                'error' => config('app.debug') ? $e->getMessage() : 'Internal server error',
            ], 500);
        }
    }

    /**
     * Call User Service to get user information
     */
    private function callUserService(int $userId, string $correlationId, ?string $authToken): array
    {
        try {
            $baseUrl = config('app.url');
            $url = "{$baseUrl}/api/users/{$userId}";

            $headers = [
                'X-Correlation-ID' => $correlationId,
                'Accept' => 'application/json',
            ];

            if ($authToken) {
                $headers['Authorization'] = str_starts_with($authToken, 'Bearer ') 
                    ? $authToken 
                    : "Bearer {$authToken}";
            }

            Log::info('Calling user service', [
                'url' => $url,
                'correlation_id' => $correlationId,
                'user_id' => $userId,
            ]);

            $response = Http::withHeaders($headers)
                ->timeout(10)
                ->get($url);

            if ($response->successful()) {
                $data = $response->json();
                return [
                    'success' => true,
                    'data' => $data['data'] ?? $data,
                ];
            }

            return [
                'success' => false,
                'error' => $response->json()['message'] ?? 'User service returned error',
                'status' => $response->status(),
            ];
        } catch (\Illuminate\Http\Client\ConnectionException $e) {
            Log::error('User service connection failed', [
                'error' => $e->getMessage(),
                'correlation_id' => $correlationId,
            ]);

            return [
                'success' => false,
                'error' => 'User service is unavailable',
            ];
        } catch (\Exception $e) {
            Log::error('User service call failed', [
                'error' => $e->getMessage(),
                'correlation_id' => $correlationId,
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Call Reservation Service to create reservation
     */
    private function callReservationService(array $data, string $correlationId, ?string $authToken): array
    {
        try {
            $baseUrl = config('app.url');
            $url = "{$baseUrl}/api/reservations";

            $headers = [
                'X-Correlation-ID' => $correlationId,
                'Accept' => 'application/json',
                'Content-Type' => 'application/json',
            ];

            if ($authToken) {
                $headers['Authorization'] = str_starts_with($authToken, 'Bearer ') 
                    ? $authToken 
                    : "Bearer {$authToken}";
            }

            Log::info('Calling reservation service', [
                'url' => $url,
                'correlation_id' => $correlationId,
            ]);

            $response = Http::withHeaders($headers)
                ->timeout(10)
                ->post($url, $data);

            if ($response->successful()) {
                $responseData = $response->json();
                return [
                    'success' => true,
                    'data' => $responseData['data'] ?? $responseData,
                ];
            }

            return [
                'success' => false,
                'error' => $response->json()['message'] ?? 'Reservation service returned error',
                'status' => $response->status(),
                'errors' => $response->json()['errors'] ?? null,
            ];
        } catch (\Illuminate\Http\Client\ConnectionException $e) {
            Log::error('Reservation service connection failed', [
                'error' => $e->getMessage(),
                'correlation_id' => $correlationId,
            ]);

            return [
                'success' => false,
                'error' => 'Reservation service is unavailable',
            ];
        } catch (\Exception $e) {
            Log::error('Reservation service call failed', [
                'error' => $e->getMessage(),
                'correlation_id' => $correlationId,
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Call Reservation Service to get reservation by ID
     */
    private function callReservationServiceById(int $reservationId, string $correlationId, ?string $authToken): array
    {
        try {
            $baseUrl = config('app.url');
            $url = "{$baseUrl}/api/reservations/{$reservationId}";

            $headers = [
                'X-Correlation-ID' => $correlationId,
                'Accept' => 'application/json',
            ];

            if ($authToken) {
                $headers['Authorization'] = str_starts_with($authToken, 'Bearer ') 
                    ? $authToken 
                    : "Bearer {$authToken}";
            }

            Log::info('Calling reservation service to get reservation', [
                'url' => $url,
                'correlation_id' => $correlationId,
                'reservation_id' => $reservationId,
            ]);

            $response = Http::withHeaders($headers)
                ->timeout(10)
                ->get($url);

            if ($response->successful()) {
                $responseData = $response->json();
                return [
                    'success' => true,
                    'data' => $responseData['data'] ?? $responseData,
                ];
            }

            return [
                'success' => false,
                'error' => $response->json()['message'] ?? 'Reservation service returned error',
                'status' => $response->status(),
            ];
        } catch (\Illuminate\Http\Client\ConnectionException $e) {
            Log::error('Reservation service connection failed', [
                'error' => $e->getMessage(),
                'correlation_id' => $correlationId,
            ]);

            return [
                'success' => false,
                'error' => 'Reservation service is unavailable',
            ];
        } catch (\Exception $e) {
            Log::error('Reservation service call failed', [
                'error' => $e->getMessage(),
                'correlation_id' => $correlationId,
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Call Reservation Service to get list of reservations
     */
    private function callReservationServiceForList(string $correlationId, ?string $authToken): array
    {
        try {
            $baseUrl = config('app.url');
            $url = "{$baseUrl}/api/reservations";

            $headers = [
                'X-Correlation-ID' => $correlationId,
                'Accept' => 'application/json',
            ];

            if ($authToken) {
                $headers['Authorization'] = str_starts_with($authToken, 'Bearer ') 
                    ? $authToken 
                    : "Bearer {$authToken}";
            }

            Log::info('Calling reservation service for list', [
                'url' => $url,
                'correlation_id' => $correlationId,
            ]);

            $response = Http::withHeaders($headers)
                ->timeout(10)
                ->get($url);

            if ($response->successful()) {
                $responseData = $response->json();
                return [
                    'success' => true,
                    'data' => $responseData,
                ];
            }

            return [
                'success' => false,
                'error' => $response->json()['message'] ?? 'Reservation service returned error',
                'status' => $response->status(),
            ];
        } catch (\Illuminate\Http\Client\ConnectionException $e) {
            Log::error('Reservation service connection failed for list', [
                'error' => $e->getMessage(),
                'correlation_id' => $correlationId,
            ]);

            return [
                'success' => false,
                'error' => 'Reservation service is unavailable',
            ];
        } catch (\Exception $e) {
            Log::error('Reservation service call failed for list', [
                'error' => $e->getMessage(),
                'correlation_id' => $correlationId,
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }
}

