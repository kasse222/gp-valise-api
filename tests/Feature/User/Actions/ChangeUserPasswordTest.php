<?php
uses(
    Tests\TestCase::class,
    Illuminate\Foundation\Testing\RefreshDatabase::class
);

use App\Models\User;
use App\Actions\User\ChangeUserPassword;
use Illuminate\Support\Facades\Hash;

it('change le mot de passe de l’utilisateur', function () {
    $user = User::factory()->create([
        'password' => Hash::make('ancien'),
    ]);

    ChangeUserPassword::execute($user, 'nouveauMotDePasse123');

    $user->refresh();

    expect(Hash::check('nouveauMotDePasse123', $user->password))->toBeTrue();
});
