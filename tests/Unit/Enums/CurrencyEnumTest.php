<?php

declare(strict_types=1);

use App\Enums\CurrencyEnum;

// ─── forCountry() ─────────────────────────────────────────────────────────

it('forCountry retourne XOF pour SN', function () {
    expect(CurrencyEnum::forCountry('SN'))->toBe(CurrencyEnum::XOF);
});

it('forCountry retourne XOF pour CI, BJ, TG, ML, BF, GW, NE', function () {
    foreach (['CI', 'BJ', 'TG', 'ML', 'BF', 'GW', 'NE'] as $country) {
        expect(CurrencyEnum::forCountry($country))->toBe(CurrencyEnum::XOF);
    }
});

it('forCountry retourne EUR pour FR, BE, DE, ES', function () {
    foreach (['FR', 'BE', 'DE', 'ES'] as $country) {
        expect(CurrencyEnum::forCountry($country))->toBe(CurrencyEnum::EUR);
    }
});

it('forCountry retourne MAD pour MA', function () {
    expect(CurrencyEnum::forCountry('MA'))->toBe(CurrencyEnum::MAD);
});

it('forCountry retourne GBP pour GB', function () {
    expect(CurrencyEnum::forCountry('GB'))->toBe(CurrencyEnum::GBP);
});

it('forCountry retourne USD pour US', function () {
    expect(CurrencyEnum::forCountry('US'))->toBe(CurrencyEnum::USD);
});

it('forCountry est insensible à la casse', function () {
    expect(CurrencyEnum::forCountry('sn'))->toBe(CurrencyEnum::XOF)
        ->and(CurrencyEnum::forCountry('fr'))->toBe(CurrencyEnum::EUR)
        ->and(CurrencyEnum::forCountry('ma'))->toBe(CurrencyEnum::MAD);
});

it('forCountry retourne EUR par défaut pour pays inconnu', function () {
    expect(CurrencyEnum::forCountry('XX'))->toBe(CurrencyEnum::EUR)
        ->and(CurrencyEnum::forCountry(''))->toBe(CurrencyEnum::EUR);
});

// ─── hasSubunit() ──────────────────────────────────────────────────────────

it('hasSubunit retourne false pour XOF — pas de centime', function () {
    expect(CurrencyEnum::XOF->hasSubunit())->toBeFalse();
});

it('hasSubunit retourne true pour EUR, MAD, GBP, USD', function () {
    foreach ([CurrencyEnum::EUR, CurrencyEnum::MAD, CurrencyEnum::GBP, CurrencyEnum::USD] as $currency) {
        expect($currency->hasSubunit())->toBeTrue();
    }
});

// ─── symbol() et label() ──────────────────────────────────────────────────

it('symbol retourne les bons symboles', function () {
    expect(CurrencyEnum::EUR->symbol())->toBe('€')
        ->and(CurrencyEnum::XOF->symbol())->toBe('CFA')
        ->and(CurrencyEnum::MAD->symbol())->toBe('DH')
        ->and(CurrencyEnum::GBP->symbol())->toBe('£')
        ->and(CurrencyEnum::USD->symbol())->toBe('$');
});
