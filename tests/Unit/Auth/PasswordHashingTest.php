<?php

use Illuminate\Support\Facades\Hash;

test('password is hashed correctly', function () {
    $plainPassword = 'password123';
    $hashedPassword = Hash::make($plainPassword);

    expect($hashedPassword)->not->toBe($plainPassword)
        ->and(Hash::check($plainPassword, $hashedPassword))->toBeTrue();
});

test('different passwords produce different hashes', function () {
    $password1 = 'password123';
    $password2 = 'password456';

    $hash1 = Hash::make($password1);
    $hash2 = Hash::make($password2);

    expect($hash1)->not->toBe($hash2);
});

test('same password produces different hashes each time', function () {
    $password = 'password123';
    
    $hash1 = Hash::make($password);
    $hash2 = Hash::make($password);

    // Same password should produce different hashes (due to salt)
    expect($hash1)->not->toBe($hash2)
        ->and(Hash::check($password, $hash1))->toBeTrue()
        ->and(Hash::check($password, $hash2))->toBeTrue();
});

test('wrong password does not match hash', function () {
    $correctPassword = 'password123';
    $wrongPassword = 'wrongpassword';
    
    $hash = Hash::make($correctPassword);

    expect(Hash::check($wrongPassword, $hash))->toBeFalse();
});

