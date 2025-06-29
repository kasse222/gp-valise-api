<?php
uses(
    Tests\TestCase::class,
    Illuminate\Foundation\Testing\RefreshDatabase::class
);

use App\Http\Requests\Auth\LogoutRequest;
use Illuminate\Support\Facades\Validator;

it('valide une requête de logout avec token valide', function () {
    $data = [
        'token' => 'sometoken123456',
    ];

    $validator = Validator::make(
        $data,
        (new LogoutRequest())->rules(),
        (new LogoutRequest())->messages(),
        (new LogoutRequest())->attributes()
    );

    expect($validator->fails())->toBeFalse();
});

it('rejette une requête de logout invalide (token manquant)', function () {
    $data = [];

    $validator = Validator::make(
        $data,
        (new LogoutRequest())->rules(),
        (new LogoutRequest())->messages(),
        (new LogoutRequest())->attributes()
    );

    expect($validator->fails())->toBeTrue();
    expect($validator->errors()->has('token'))->toBeTrue();
});
