<?php

use App\Actions\Auth\RegisterUser;
use App\Enums\UserRoleEnum;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;

uses(Tests\TestCase::class, RefreshDatabase::class);

it('crée un utilisateur avec les données fournies', function () {
    $action = app(RegisterUser::class);

    $user = $action->execute([
        'first_name' => 'Lamine',
        'last_name'  => 'Kasse',
        'email'      => 'lamine@example.com',
        'password'   => 'secret123',
        'role'       => UserRoleEnum::SENDER->value,
        'phone'      => '+212600000001',
        'country'    => 'MA',
    ]);

    expect($user)->toBeInstanceOf(User::class)
        ->and($user->first_name)->toBe('Lamine')
        ->and($user->email)->toBe('lamine@example.com')
        ->and($user->role)->toBe(UserRoleEnum::SENDER)
        ->and($user->verified_user)->toBeFalse()
        ->and($user->kyc_passed_at)->toBeNull();

    expect(User::where('email', 'lamine@example.com')->exists())->toBeTrue();
});

it('hash le mot de passe à la création', function () {
    $action = app(RegisterUser::class);

    $user = $action->execute([
        'first_name' => 'Test',
        'last_name'  => 'User',
        'email'      => 'test@example.com',
        'password'   => 'plaintext',
        'role'       => UserRoleEnum::TRAVELER->value,
        'phone'      => '+212600000002',
        'country'    => null,
    ]);

    expect(Hash::check('plaintext', $user->password))->toBeTrue()
        ->and($user->password)->not->toBe('plaintext');
});

it('country nullable est accepté', function () {
    $action = app(RegisterUser::class);

    $user = $action->execute([
        'first_name' => 'No',
        'last_name'  => 'Country',
        'email'      => 'nocountry@example.com',
        'password'   => 'secret',
        'role'       => UserRoleEnum::TRAVELER->value,
        'phone'      => '+212600000003',
    ]);

    expect($user->country)->toBeNull();
});
