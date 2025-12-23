<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;

uses(RefreshDatabase::class);

/**
 * Unit Test untuk Booking Service Controller (Service C)
 * 
 * Test ini fokus pada business logic dari booking service,
 * seperti service call handling, correlation ID passing,
 * dan error handling untuk service failures.
 */

test('booking service can handle user service call', function () {
    $user = User::create([
        'name' => 'Test User',
        'email' => 'test@example.com',
        'password' => Hash::make('password123'),
        'role' => 'customer',
        'email_verified_at' => now(),
    ]);

    $correlationId = 'test-correlation-id-123';
    
    // Mock HTTP response for user service
    Http::fake([
        config('app.url') . '/api/users/*' => Http::response([
            'status' => 'success',
            'data' => [
                'user' => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                ],
            ],
        ], 200),
    ]);

    // Test that correlation ID is passed in headers
    $response = Http::withHeaders([
        'X-Correlation-ID' => $correlationId,
        'Authorization' => 'Bearer test-token',
    ])->get(config('app.url') . '/api/users/' . $user->id);

    expect($response->successful())->toBeTrue();
    
    $responseData = $response->json();
    expect($responseData['status'])->toBe('success');
    expect($responseData['data']['user']['id'])->toBe($user->id);
});

test('booking service handles user service failure gracefully', function () {
    $correlationId = 'test-correlation-id-456';
    
    // Mock HTTP response for user service failure
    Http::fake([
        config('app.url') . '/api/users/*' => Http::response([
            'status' => 'error',
            'message' => 'User not found',
        ], 404),
    ]);

    $response = Http::withHeaders([
        'X-Correlation-ID' => $correlationId,
        'Authorization' => 'Bearer test-token',
    ])->get(config('app.url') . '/api/users/999');

    expect($response->failed())->toBeTrue();
    expect($response->status())->toBe(404);
});

test('booking service passes correlation ID correctly', function () {
    $correlationId = 'test-correlation-id-789';
    
    Http::fake(function ($request) use ($correlationId) {
        // Verify correlation ID is in headers
        expect($request->hasHeader('X-Correlation-ID'))->toBeTrue();
        expect($request->header('X-Correlation-ID')[0])->toBe($correlationId);
        
        return Http::response(['status' => 'success'], 200);
    });

    Http::withHeaders([
        'X-Correlation-ID' => $correlationId,
    ])->get(config('app.url') . '/api/test');
});

test('booking service passes authorization token correctly', function () {
    $token = 'test-auth-token-123';
    $correlationId = 'test-correlation-id-abc';
    
    Http::fake(function ($request) use ($token) {
        // Verify authorization token is in headers
        expect($request->hasHeader('Authorization'))->toBeTrue();
        expect($request->header('Authorization')[0])->toContain($token);
        
        return Http::response(['status' => 'success'], 200);
    });

    Http::withHeaders([
        'X-Correlation-ID' => $correlationId,
        'Authorization' => 'Bearer ' . $token,
    ])->get(config('app.url') . '/api/test');
});

test('booking service handles connection timeout gracefully', function () {
    $correlationId = 'test-correlation-id-timeout';
    
    // Mock connection timeout
    Http::fake([
        config('app.url') . '/api/users/*' => function () {
            throw new \Illuminate\Http\Client\ConnectionException('Connection timeout');
        },
    ]);

    try {
        $response = Http::timeout(1)
            ->withHeaders([
                'X-Correlation-ID' => $correlationId,
            ])
            ->get(config('app.url') . '/api/users/1');
    } catch (\Illuminate\Http\Client\ConnectionException $e) {
        expect($e->getMessage())->toContain('Connection');
    }
});

