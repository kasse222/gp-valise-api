<?php

declare(strict_types=1);

use App\Enums\CurrencyEnum;
use App\Models\PlatformAccount;
use App\Services\PlatformAccountResolver;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(Tests\TestCase::class, RefreshDatabase::class);

beforeEach(function (): void {
    $this->resolver = app(PlatformAccountResolver::class);
});

it('résout un compte par pays et devise', function (): void {
    $account = PlatformAccount::factory()->xof()->create();

    $resolved = $this->resolver->resolveForCountry('SN', CurrencyEnum::XOF);

    expect($resolved->id)->toBe($account->id)
        ->and($resolved->currency)->toBe(CurrencyEnum::XOF)
        ->and($resolved->country_code)->toBe('SN');
});

it('résout en ignorant la casse du country_code', function (): void {
    PlatformAccount::factory()->xof()->create();

    $resolved = $this->resolver->resolveForCountry('sn', CurrencyEnum::XOF);

    expect($resolved->currency)->toBe(CurrencyEnum::XOF);
});

it('lève une exception si aucun compte actif pour ce pays et devise', function (): void {
    expect(fn() => $this->resolver->resolveForCountry('SN', CurrencyEnum::XOF))
        ->toThrow(RuntimeException::class, 'No active platform account for country [SN] and currency [XOF].');
});

it('ignore les comptes inactifs dans resolveForCountry', function (): void {
    PlatformAccount::factory()->xof()->inactive()->create();

    expect(fn() => $this->resolver->resolveForCountry('SN', CurrencyEnum::XOF))
        ->toThrow(RuntimeException::class);
});

it('résout un compte par devise uniquement', function (): void {
    $account = PlatformAccount::factory()->eur()->create();

    $resolved = $this->resolver->resolveByCurrency(CurrencyEnum::EUR);

    expect($resolved->id)->toBe($account->id);
});

it('lève une exception si aucun compte actif pour cette devise', function (): void {
    expect(fn() => $this->resolver->resolveByCurrency(CurrencyEnum::XOF))
        ->toThrow(RuntimeException::class, 'No active platform account for currency [XOF].');
});

it('résout un compte par provider et devise', function (): void {
    $account = PlatformAccount::factory()->xof()->create();

    $resolved = $this->resolver->resolveByProvider('kkiapay', CurrencyEnum::XOF);

    expect($resolved->id)->toBe($account->id)
        ->and($resolved->provider)->toBe('kkiapay');
});

it('lève une exception si aucun compte actif pour ce provider et devise', function (): void {
    expect(fn() => $this->resolver->resolveByProvider('stripe', CurrencyEnum::XOF))
        ->toThrow(RuntimeException::class, 'No active platform account for provider [stripe] and currency [XOF].');
});

it('ignore les comptes inactifs dans resolveByProvider', function (): void {
    PlatformAccount::factory()->xof()->inactive()->create();

    expect(fn() => $this->resolver->resolveByProvider('kkiapay', CurrencyEnum::XOF))
        ->toThrow(RuntimeException::class);
});
