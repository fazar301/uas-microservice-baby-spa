<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;

uses(RefreshDatabase::class);

test('user can register via API', function () {
    $response = $this->postJson('/api/register', [
        'name' => 'Test User',
        'email' => 'test@example.com',
        'password' => 'password123',
        'password_confirmation' => 'password123',
        'noHP' => '081234567890',
    ]);

    $response->assertStatus(201)
        ->assertJsonStructure([
            'status',
            'message',
            'data' => [
                'user' => [
                    'id',
                    'name',
                    'email',
                    'noHP',
                    'role',
                ],
                'token',
                'token_type',
            ],
        ])
        ->assertJson([
            'status' => 'success',
            'message' => 'User registered successfully',
        ]);

    $this->assertDatabaseHas('users', [
        'email' => 'test@example.com',
        'name' => 'Test User',
    ]);

    // Verify that email_verified_at is set (auto-verified)
    $user = User::where('email', 'test@example.com')->first();
    expect($user->email_verified_at)->not->toBeNull();
});

test('user registration fails with invalid data', function () {
    $response = $this->postJson('/api/register', [
        'name' => 'AB', // Too short
        'email' => 'invalid-email', // Invalid email
        'password' => '123', // Too short and no letters
        'password_confirmation' => '456', // Doesn't match
    ]);

    $response->assertStatus(422)
        ->assertJsonValidationErrors(['name', 'email', 'password']);
});

test('user can login via API', function () {
    $user = User::factory()->create([
        'email' => 'test@example.com',
        'password' => Hash::make('password123'),
    ]);

    $response = $this->postJson('/api/login', [
        'email' => 'test@example.com',
        'password' => 'password123',
    ]);

    $response->assertStatus(200)
        ->assertJsonStructure([
            'status',
            'message',
            'data' => [
                'user' => [
                    'id',
                    'name',
                    'email',
                    'role',
                ],
                'token',
                'token_type',
            ],
        ])
        ->assertJson([
            'status' => 'success',
            'message' => 'Login successful',
        ]);

    expect($response->json('data.token'))->not->toBeEmpty();
});

test('user login fails with invalid credentials', function () {
    $user = User::factory()->create([
        'email' => 'test@example.com',
        'password' => Hash::make('password123'),
    ]);

    $response = $this->postJson('/api/login', [
        'email' => 'test@example.com',
        'password' => 'wrong-password',
    ]);

    $response->assertStatus(422)
        ->assertJsonValidationErrors(['email']);
});

test('authenticated user can get profile', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user, 'sanctum')
        ->getJson('/api/profile');

    $response->assertStatus(200)
        ->assertJsonStructure([
            'status',
            'data' => [
                'user' => [
                    'id',
                    'name',
                    'email',
                    'noHP',
                    'role',
                ],
            ],
        ])
        ->assertJson([
            'status' => 'success',
            'data' => [
                'user' => [
                    'id' => $user->id,
                    'email' => $user->email,
                ],
            ],
        ]);
});

test('authenticated user can update profile', function () {
    $user = User::factory()->create([
        'name' => 'Old Name',
        'email' => 'old@example.com',
    ]);

    $response = $this->actingAs($user, 'sanctum')
        ->putJson('/api/profile', [
            'name' => 'New Name',
            'email' => 'new@example.com',
        ]);

    $response->assertStatus(200)
        ->assertJson([
            'status' => 'success',
            'message' => 'Profile updated successfully',
        ]);

    $this->assertDatabaseHas('users', [
        'id' => $user->id,
        'name' => 'New Name',
        'email' => 'new@example.com',
    ]);
});

test('authenticated user can logout', function () {
    $user = User::factory()->create();
    $token = $user->createToken('test-token')->plainTextToken;

    $response = $this->withHeader('Authorization', 'Bearer ' . $token)
        ->postJson('/api/logout');

    $response->assertStatus(200)
        ->assertJson([
            'status' => 'success',
            'message' => 'Logged out successfully',
        ]);

    // Verify token is deleted
    expect($user->tokens()->count())->toBe(0);
});

test('admin can get all users', function () {
    $admin = User::factory()->create(['role' => 'admin']);
    User::factory()->count(3)->create();

    $response = $this->actingAs($admin, 'sanctum')
        ->getJson('/api/users');

    $response->assertStatus(200)
        ->assertJsonStructure([
            'status',
            'data' => [
                'data' => [
                    '*' => [
                        'id',
                        'name',
                        'email',
                    ],
                ],
            ],
        ]);
});

test('non-admin cannot get all users', function () {
    $user = User::factory()->create(['role' => 'customer']);

    $response = $this->actingAs($user, 'sanctum')
        ->getJson('/api/users');

    $response->assertStatus(403)
        ->assertJson([
            'status' => 'error',
            'message' => 'Unauthorized. Admin access required.',
        ]);
});

test('admin can get specific user', function () {
    $admin = User::factory()->create(['role' => 'admin']);
    $targetUser = User::factory()->create();

    $response = $this->actingAs($admin, 'sanctum')
        ->getJson("/api/users/{$targetUser->id}");

    $response->assertStatus(200)
        ->assertJson([
            'status' => 'success',
            'data' => [
                'user' => [
                    'id' => $targetUser->id,
                    'email' => $targetUser->email,
                ],
            ],
        ]);
});

test('admin can delete user', function () {
    $admin = User::factory()->create(['role' => 'admin']);
    $targetUser = User::factory()->create();

    $response = $this->actingAs($admin, 'sanctum')
        ->deleteJson("/api/users/{$targetUser->id}");

    $response->assertStatus(200)
        ->assertJson([
            'status' => 'success',
            'message' => 'User deleted successfully',
        ]);

    $this->assertDatabaseMissing('users', [
        'id' => $targetUser->id,
    ]);
});

test('admin cannot delete own account', function () {
    $admin = User::factory()->create(['role' => 'admin']);

    $response = $this->actingAs($admin, 'sanctum')
        ->deleteJson("/api/users/{$admin->id}");

    $response->assertStatus(400)
        ->assertJson([
            'status' => 'error',
            'message' => 'You cannot delete your own account.',
        ]);
});

test('correlation ID is included in response header', function () {
    $response = $this->postJson('/api/register', [
        'name' => 'Test User',
        'email' => 'test@example.com',
        'password' => 'password123',
        'password_confirmation' => 'password123',
    ], [
        'X-Correlation-ID' => 'test-correlation-id-123',
    ]);

    $response->assertStatus(201)
        ->assertHeader('X-Correlation-ID', 'test-correlation-id-123');
});

test('correlation ID is generated if not provided', function () {
    $response = $this->postJson('/api/register', [
        'name' => 'Test User',
        'email' => 'test@example.com',
        'password' => 'password123',
        'password_confirmation' => 'password123',
    ]);

    $response->assertStatus(201)
        ->assertHeader('X-Correlation-ID');

    expect($response->headers->get('X-Correlation-ID'))->not->toBeEmpty();
});

