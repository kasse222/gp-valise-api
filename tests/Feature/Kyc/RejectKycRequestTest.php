<?php

declare(strict_types=1);

use App\Actions\Kyc\RejectKycRequest;
use App\Enums\KycStatusEnum;
use App\Models\KycRequest;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;

uses(Tests\TestCase::class, RefreshDatabase::class);

beforeEach(function (): void {
    $this->admin  = User::factory()->admin()->create();
    $this->sender = User::factory()->sender()->verified()->create([
        'kyc_passed_at' => now(),
    ]);
    $this->kyc = KycRequest::factory()->create([
        'user_id' => $this->sender->id,
        'status'  => KycStatusEnum::PENDING,
    ]);
});

it('rejette une demande KYC en attente', function (): void {
    $kyc = app(RejectKycRequest::class)->execute($this->kyc, $this->admin, 'Documents illisibles');

    expect($kyc->status)->toBe(KycStatusEnum::REJECTED)
        ->and($kyc->reviewed_by)->toBe($this->admin->id)
        ->and($kyc->reviewed_at)->not->toBeNull()
        ->and($kyc->rejection_reason)->toBe('Documents illisibles');

    $this->sender->refresh();
    expect($this->sender->kyc_passed_at)->toBeNull();
});

it('refuse le rejet si raison vide', function (): void {
    expect(fn() => app(RejectKycRequest::class)->execute($this->kyc, $this->admin, '   '))
        ->toThrow(ValidationException::class);
});

it('refuse le rejet si KYC non pending', function (): void {
    $this->kyc->update(['status' => KycStatusEnum::REJECTED]);

    expect(fn() => app(RejectKycRequest::class)->execute($this->kyc, $this->admin, 'Raison'))
        ->toThrow(ValidationException::class);
});

it('remet kyc_passed_at à null après rejet', function (): void {
    app(RejectKycRequest::class)->execute($this->kyc, $this->admin, 'Documents expirés');

    $this->sender->refresh();
    expect($this->sender->kyc_passed_at)->toBeNull();
});
