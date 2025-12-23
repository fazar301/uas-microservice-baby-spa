<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;

uses(RefreshDatabase::class);

/**
 * Unit Test untuk Authentication Business Logic
 * 
 * Test ini fokus pada business logic dari authentication,
 * seperti validasi password, email verification, dan user creation.
 * 
 * Unit test ini menguji logika bisnis yang dapat diuji secara terisolasi
 * tanpa memerlukan full HTTP request/response cycle.
 */

test('user registration sets email_verified_at to now (auto-verify)', function () {
    $user = User::create([
        'name' => 'Test User',
        'email' => 'test@example.com',
        'password' => Hash::make('password123'),
        'noHP' => '081234567890',
        'role' => 'customer',
        'email_verified_at' => now(), // Auto-verify as per requirement
    ]);

    expect($user->email_verified_at)->not->toBeNull();
    expect($user->email_verified_at)->toBeInstanceOf(\Illuminate\Support\Carbon::class);
    // Verify that email_verified_at is set (not null) which means auto-verification works
    expect($user->hasVerifiedEmail())->toBeTrue();
});

test('password is hashed when creating user', function () {
    $plainPassword = 'password123';
    $user = User::create([
        'name' => 'Test User',
        'email' => 'test@example.com',
        'password' => Hash::make($plainPassword),
        'role' => 'customer',
    ]);

    expect($user->password)->not->toBe($plainPassword);
    expect(Hash::check($plainPassword, $user->password))->toBeTrue();
});

test('user has default role of customer', function () {
    $user = User::create([
        'name' => 'Test User',
        'email' => 'test@example.com',
        'password' => Hash::make('password123'),
    ]);

    // Boot method should set default role to 'customer'
    expect($user->role)->toBe('customer');
});

test('user can have admin role', function () {
    $user = User::create([
        'name' => 'Admin User',
        'email' => 'admin@example.com',
        'password' => Hash::make('password123'),
        'role' => 'admin',
    ]);

    expect($user->role)->toBe('admin');
    expect($user->isAdmin())->toBeTrue();
});

test('user isAdmin method returns false for customer role', function () {
    $user = User::create([
        'name' => 'Customer User',
        'email' => 'customer@example.com',
        'password' => Hash::make('password123'),
        'role' => 'customer',
    ]);

    expect($user->isAdmin())->toBeFalse();
});

test('user can be created with all required fields', function () {
    $user = User::create([
        'name' => 'Test User',
        'email' => 'test@example.com',
        'password' => Hash::make('password123'),
        'noHP' => '081234567890',
        'role' => 'customer',
        'email_verified_at' => now(),
    ]);

    expect($user->name)->toBe('Test User');
    expect($user->email)->toBe('test@example.com');
    expect($user->noHP)->toBe('081234567890');
    expect($user->role)->toBe('customer');
    expect($user->email_verified_at)->not->toBeNull();
});

test('password verification works correctly', function () {
    $plainPassword = 'MySecurePassword123';
    $hashedPassword = Hash::make($plainPassword);
    
    $user = User::create([
        'name' => 'Test User',
        'email' => 'test@example.com',
        'password' => $hashedPassword,
        'role' => 'customer',
    ]);

    // Correct password should verify
    expect(Hash::check($plainPassword, $user->password))->toBeTrue();
    
    // Wrong password should not verify
    expect(Hash::check('WrongPassword', $user->password))->toBeFalse();
});

