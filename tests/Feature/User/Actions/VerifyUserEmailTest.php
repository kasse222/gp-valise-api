<?php
uses(
    Tests\TestCase::class,
    Illuminate\Foundation\Testing\RefreshDatabase::class
);

use App\Models\User;
use App\Actions\User\VerifyUserEmail;
use Illuminate\Support\Carbon;


it('met à jour la vérification email', function () {
    $fakeNow = Carbon::parse('2025-07-03 22:00:00');
    Carbon::setTestNow($fakeNow); // ← gel du temps

    $user = User::factory()->create([
        'email_verified_at' => null,
    ]);

    VerifyUserEmail::execute($user);

    $user->refresh();

    expect($user->email_verified_at)->not()->toBeNull()
        ->and($user->email_verified_at->eq($fakeNow))->toBeTrue(); // ← compare au même instant figé
});
