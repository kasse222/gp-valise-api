<?php
uses(
    Tests\TestCase::class,
    Illuminate\Foundation\Testing\RefreshDatabase::class
);

use App\Enums\UserRoleEnum;
use App\Http\Requests\Auth\RegisterRequest;
use Illuminate\Support\Facades\Validator;

it('valide une requête de register valide', function () {
    $data = [
        'first_name' => 'Lamine',
        'last_name'  => 'Kasse',
        'email'      => 'lamine@example.com',
        'password'   => 'password123',
        'password_confirmation' => 'password123',
        'phone'      => '770000000',
        'country'    => 'SN',
        'role'       => UserRoleEnum::SENDER->value,
    ];

    $validator = Validator::make(
        $data,
        (new RegisterRequest())->rules(),
        (new RegisterRequest())->messages(),
        (new RegisterRequest())->attributes()
    );

    expect($validator->fails())->toBeFalse();
});

it('rejette une requête de register invalide', function () {
    $data = [
        'first_name' => '', // manquant
        'email'      => 'not-an-email',
        'password'   => 'short',
        'password_confirmation' => '', // manque la confirmation
        'role'       => 999, // valeur invalide
    ];

    $validator = Validator::make(
        $data,
        (new RegisterRequest())->rules(),
        (new RegisterRequest())->messages(),
        (new RegisterRequest())->attributes()
    );

    expect($validator->fails())->toBeTrue();
    expect($validator->errors()->has('first_name'))->toBeTrue();
    expect($validator->errors()->has('email'))->toBeTrue();
    expect($validator->errors()->has('password'))->toBeTrue();
    expect($validator->errors()->has('password'))->toBeTrue();
    expect($validator->errors()->has('role'))->toBeTrue();
});
