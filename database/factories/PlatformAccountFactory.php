<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\CurrencyEnum;
use App\Models\PlatformAccount;
use Illuminate\Database\Eloquent\Factories\Factory;

final class PlatformAccountFactory extends Factory
{
    protected $model = PlatformAccount::class;

    public function definition(): array
    {
        return [
            'name'         => 'GP-Valise ' . $this->faker->country(),
            'currency'     => CurrencyEnum::EUR->value,
            'country_code' => 'FR',
            'provider'     => 'stripe',
            'is_active'    => true,
            'balance'      => 0,
            'metadata'     => null,
        ];
    }

    public function xof(): static
    {
        return $this->state([
            'name'         => 'GP-Valise Sénégal XOF',
            'currency'     => CurrencyEnum::XOF->value,
            'country_code' => 'SN',
            'provider'     => 'kkiapay',
        ]);
    }

    public function mad(): static
    {
        return $this->state([
            'name'         => 'GP-Valise Maroc MAD',
            'currency'     => CurrencyEnum::MAD->value,
            'country_code' => 'MA',
            'provider'     => 'stripe',
        ]);
    }

    public function eur(): static
    {
        return $this->state([
            'name'         => 'GP-Valise Europe EUR',
            'currency'     => CurrencyEnum::EUR->value,
            'country_code' => 'FR',
            'provider'     => 'stripe',
        ]);
    }

    public function inactive(): static
    {
        return $this->state(['is_active' => false]);
    }
}
