<?php

use App\Models\Invitation;
use App\Actions\Invitation\AcceptInvitation;
use App\Enums\InvitationStatusEnum;
use Illuminate\Support\Carbon;



uses(
    Tests\TestCase::class,
    Illuminate\Foundation\Testing\RefreshDatabase::class
);

it('accepte une invitation valide', function () {
    $invitation = Invitation::factory()->create([
        'used_at' => null,
        'status' => InvitationStatusEnum::PENDING,
        'expires_at' => now()->addDay(),
    ]);

    $result = AcceptInvitation::execute($invitation->token);

    expect($result)->toBeInstanceOf(Invitation::class)
        ->and($result->used_at)->not->toBeNull()
        ->and($result->status)->toBe(InvitationStatusEnum::USED);
});

it('échoue si le token est invalide ou expiré', function () {
    $this->expectException(\Illuminate\Database\Eloquent\ModelNotFoundException::class);

    AcceptInvitation::execute('invalid-token-123');
});
