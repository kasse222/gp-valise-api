<?php

uses(
    Tests\TestCase::class,
    Illuminate\Foundation\Testing\RefreshDatabase::class
);

use Illuminate\Support\Facades\Validator;
use App\Http\Requests\Auth\LoginRequest;


it('valide une requête de login valide', function () {
    $data = [
        'email'    => 'test@example.com',
        'password' => 'password123',
    ];

    $validator = Validator::make(
        $data,
        (new LoginRequest())->rules(),
        (new LoginRequest())->messages(),
        (new LoginRequest())->attributes()
    );

    expect($validator->fails())->toBeFalse();
});

it('rejette une requête de login invalide', function () {
    $data = [
        'email'    => 'invalid-email',
        'password' => '',
    ];

    $validator = Validator::make(
        $data,
        (new LoginRequest())->rules(),
        (new LoginRequest())->messages(),
        (new LoginRequest())->attributes()
    );

    expect($validator->fails())->toBeTrue();
    expect($validator->errors()->has('email'))->toBeTrue();
    expect($validator->errors()->has('password'))->toBeTrue();
});
