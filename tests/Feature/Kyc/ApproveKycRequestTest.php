<?php

declare(strict_types=1);

use App\Actions\Kyc\ApproveKycRequest;
use App\Enums\KycStatusEnum;
use App\Models\KycRequest;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\ValidationException;

uses(Tests\TestCase::class, RefreshDatabase::class);

beforeEach(function (): void {
    Mail::fake();
    $this->admin  = User::factory()->admin()->create();
    $this->sender = User::factory()->sender()->verified()->create();
    $this->kyc    = KycRequest::factory()->create([
        'user_id' => $this->sender->id,
        'status'  => KycStatusEnum::PENDING,
    ]);
});

it('approuve une demande KYC en attente', function (): void {
    $kyc = app(ApproveKycRequest::class)->execute($this->kyc, $this->admin, 'Documents valides');

    expect($kyc->status)->toBe(KycStatusEnum::APPROVED)
        ->and($kyc->reviewed_by)->toBe($this->admin->id)
        ->and($kyc->reviewed_at)->not->toBeNull()
        ->and($kyc->admin_notes)->toBe('Documents valides');

    $this->sender->refresh();
    expect($this->sender->kyc_passed_at)->not->toBeNull();
});

it('met à jour kyc_passed_at sur le user', function (): void {
    app(ApproveKycRequest::class)->execute($this->kyc, $this->admin);

    $this->sender->refresh();
    expect($this->sender->kyc_passed_at)->not->toBeNull()
        ->and($this->sender->kyc_passed_at->isToday())->toBeTrue();
});

it('refuse l\'approbation si KYC non pending', function (): void {
    $this->kyc->update(['status' => KycStatusEnum::APPROVED]);

    expect(fn() => app(ApproveKycRequest::class)->execute($this->kyc, $this->admin))
        ->toThrow(ValidationException::class);
});
