<?php

declare(strict_types=1);

use App\Actions\Kyc\SubmitKycRequest;
use App\Enums\KycStatusEnum;
use App\Models\KycRequest;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;

uses(Tests\TestCase::class, RefreshDatabase::class);

beforeEach(function (): void {
    $this->sender = User::factory()->sender()->verified()->create();
});

it('soumet une demande KYC avec succès', function (): void {
    $kyc = app(SubmitKycRequest::class)->execute($this->sender, [
        'id_photo_path'     => 'kyc/id_123.jpg',
        'parcel_photo_path' => 'kyc/parcel_123.jpg',
    ]);

    expect($kyc->status)->toBe(KycStatusEnum::PENDING)
        ->and($kyc->user_id)->toBe($this->sender->id)
        ->and($kyc->id_photo_path)->toBe('kyc/id_123.jpg')
        ->and($kyc->parcel_photo_path)->toBe('kyc/parcel_123.jpg')
        ->and($kyc->submitted_at)->not->toBeNull();
});

it('refuse si une demande est déjà en cours', function (): void {
    KycRequest::factory()->create([
        'user_id' => $this->sender->id,
        'status'  => KycStatusEnum::PENDING,
    ]);

    expect(fn() => app(SubmitKycRequest::class)->execute($this->sender, [
        'id_photo_path'     => 'kyc/id_123.jpg',
        'parcel_photo_path' => 'kyc/parcel_123.jpg',
    ]))->toThrow(ValidationException::class);
});

it('refuse si le KYC est déjà approuvé', function (): void {
    KycRequest::factory()->create([
        'user_id' => $this->sender->id,
        'status'  => KycStatusEnum::APPROVED,
    ]);

    expect(fn() => app(SubmitKycRequest::class)->execute($this->sender, [
        'id_photo_path'     => 'kyc/id_123.jpg',
        'parcel_photo_path' => 'kyc/parcel_123.jpg',
    ]))->toThrow(ValidationException::class);
});

it('remplace une demande rejetée', function (): void {
    KycRequest::factory()->create([
        'user_id' => $this->sender->id,
        'status'  => KycStatusEnum::REJECTED,
    ]);

    $kyc = app(SubmitKycRequest::class)->execute($this->sender, [
        'id_photo_path'     => 'kyc/id_new.jpg',
        'parcel_photo_path' => 'kyc/parcel_new.jpg',
    ]);

    expect($kyc->status)->toBe(KycStatusEnum::PENDING)
        ->and(KycRequest::where('user_id', $this->sender->id)->count())->toBe(1);
});
