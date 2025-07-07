<?php

use App\Models\User;
use App\Actions\Invitation\SendInvitation;
use App\Models\Invitation;
use App\Enums\InvitationStatusEnum;

uses(
    Tests\TestCase::class,
    Illuminate\Foundation\Testing\RefreshDatabase::class
);

it('crée une invitation sans recipient_id si le destinataire n’est pas inscrit', function () {
    $sender = User::factory()->create();
    $recipientEmail = 'newuser@example.com';

    $invitation = SendInvitation::execute($sender, $recipientEmail);

    expect($invitation)->toBeInstanceOf(Invitation::class)
        ->and($invitation->sender_id)->toBe($sender->id)
        ->and($invitation->recipient_email)->toBe($recipientEmail)
        ->and($invitation->recipient_id)->toBeNull()
        ->and($invitation->status)->toBe(InvitationStatusEnum::PENDING);
});

it('crée une invitation avec recipient_id si le destinataire est déjà inscrit', function () {
    $sender = User::factory()->create();
    $recipient = User::factory()->create();

    $invitation = SendInvitation::execute($sender, $recipient->email);

    expect($invitation->recipient_id)->toBe($recipient->id);
});
