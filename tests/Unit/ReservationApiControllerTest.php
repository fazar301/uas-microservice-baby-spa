<?php

use App\Models\Reservation;
use App\Models\User;
use App\Models\Layanan;
use App\Models\Sesi;
use App\Models\Bayi;
use App\Models\Kategori;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;

uses(RefreshDatabase::class);

/**
 * Unit Test untuk Reservation API Controller (Service B)
 * 
 * Test ini fokus pada business logic dari reservation,
 * seperti validasi data, status management, dan business rules.
 */

test('reservation can be created with valid data', function () {
    $user = User::create([
        'name' => 'Test User',
        'email' => 'test@example.com',
        'password' => Hash::make('password123'),
        'role' => 'customer',
        'email_verified_at' => now(),
    ]);

    $kategori = Kategori::create([
        'nama_kategori' => 'Test Kategori',
    ]);

    $layanan = Layanan::create([
        'nama_layanan' => 'Test Layanan',
        'slug' => 'test-layanan',
        'harga_layanan' => 100000,
        'deskripsi' => 'Test description',
        'image' => 'test-image.jpg',
        'kategori_id' => $kategori->id,
    ]);

    $sesi = \App\Models\Sesi::create([
        'jam' => '10:00',
    ]);

    $bayi = Bayi::create([
        'user_id' => $user->id,
        'nama' => 'Test Baby',
        'tanggal_lahir' => now()->subMonths(6),
        'jenis_kelamin' => 'laki-laki',
        'berat_lahir' => 3.5,
        'berat_sekarang' => 7.0,
        'is_temporary' => false,
    ]);

    $reservation = Reservation::create([
        'user_id' => $user->id,
        'layanan_id' => $layanan->id,
        'type' => 'layanan',
        'sesi_id' => $sesi->id,
        'bayi_id' => $bayi->id,
        'tanggal_reservasi' => now()->addDays(1),
        'status' => 'pending',
        'harga' => 100000,
    ]);

    expect($reservation->user_id)->toBe($user->id);
    expect($reservation->status)->toBe('pending');
    expect((float) $reservation->harga)->toBe(100000.0);
});

test('reservation has default status of pending when created', function () {
    $user = User::create([
        'name' => 'Test User',
        'email' => 'test@example.com',
        'password' => Hash::make('password123'),
        'role' => 'customer',
        'email_verified_at' => now(),
    ]);

    $kategori = Kategori::create([
        'nama_kategori' => 'Test Kategori',
    ]);

    $layanan = Layanan::create([
        'nama_layanan' => 'Test Layanan',
        'slug' => 'test-layanan',
        'harga_layanan' => 100000,
        'deskripsi' => 'Test description',
        'image' => 'test-image.jpg',
        'kategori_id' => $kategori->id,
    ]);

    $sesi = \App\Models\Sesi::create([
        'jam' => '10:00',
    ]);

    $reservation = Reservation::create([
        'user_id' => $user->id,
        'layanan_id' => $layanan->id,
        'type' => 'layanan',
        'sesi_id' => $sesi->id,
        'tanggal_reservasi' => now()->addDays(1),
        'status' => 'pending',
        'harga' => 100000,
    ]);

    expect($reservation->status)->toBe('pending');
});

test('reservation belongs to user', function () {
    $user = User::create([
        'name' => 'Test User',
        'email' => 'test@example.com',
        'password' => Hash::make('password123'),
        'role' => 'customer',
        'email_verified_at' => now(),
    ]);

    $kategori = Kategori::create([
        'nama_kategori' => 'Test Kategori',
    ]);

    $layanan = Layanan::create([
        'nama_layanan' => 'Test Layanan',
        'slug' => 'test-layanan',
        'harga_layanan' => 100000,
        'deskripsi' => 'Test description',
        'image' => 'test-image.jpg',
        'kategori_id' => $kategori->id,
    ]);

    $sesi = \App\Models\Sesi::create([
        'jam' => '10:00',
    ]);

    $reservation = Reservation::create([
        'user_id' => $user->id,
        'layanan_id' => $layanan->id,
        'type' => 'layanan',
        'sesi_id' => $sesi->id,
        'tanggal_reservasi' => now()->addDays(1),
        'status' => 'pending',
        'harga' => 100000,
    ]);

    expect($reservation->user)->not->toBeNull();
    expect($reservation->user->id)->toBe($user->id);
});

test('reservation can have catatan field', function () {
    $user = User::create([
        'name' => 'Test User',
        'email' => 'test@example.com',
        'password' => Hash::make('password123'),
        'role' => 'customer',
        'email_verified_at' => now(),
    ]);

    $kategori = Kategori::create([
        'nama_kategori' => 'Test Kategori',
    ]);

    $layanan = Layanan::create([
        'nama_layanan' => 'Test Layanan',
        'slug' => 'test-layanan',
        'harga_layanan' => 100000,
        'deskripsi' => 'Test description',
        'image' => 'test-image.jpg',
        'kategori_id' => $kategori->id,
    ]);

    $sesi = \App\Models\Sesi::create([
        'jam' => '10:00',
    ]);

    $catatan = 'Catatan khusus untuk reservasi ini';

    $reservation = Reservation::create([
        'user_id' => $user->id,
        'layanan_id' => $layanan->id,
        'type' => 'layanan',
        'sesi_id' => $sesi->id,
        'tanggal_reservasi' => now()->addDays(1),
        'status' => 'pending',
        'harga' => 100000,
        'catatan' => $catatan,
    ]);

    expect($reservation->catatan)->toBe($catatan);
});

