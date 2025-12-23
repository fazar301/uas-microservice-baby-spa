<?php

use App\Models\User;

test('isAdmin returns true when user role is admin', function () {
    $user = new User();
    $user->role = 'admin';

    expect($user->isAdmin())->toBeTrue();
});

test('isAdmin returns false when user role is customer', function () {
    $user = new User();
    $user->role = 'customer';

    expect($user->isAdmin())->toBeFalse();
});

test('isAdmin returns false when user role is not admin', function () {
    $user = new User();
    $user->role = 'staff';

    expect($user->isAdmin())->toBeFalse();
});

test('isAdmin returns false when user role is null', function () {
    $user = new User();
    $user->role = null;

    expect($user->isAdmin())->toBeFalse();
});

