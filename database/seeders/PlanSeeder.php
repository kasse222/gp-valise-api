<?php

namespace Database\Seeders;

use App\Models\Plan;
use Illuminate\Database\Seeder;
use App\Enums\PlanTypeEnum;
use Illuminate\Support\Carbon;

class PlanSeeder extends Seeder
{
    public function run(): void
    {
        $plans = [
            [
                'name'              => 'Plan Gratuit',
                'type'              => PlanTypeEnum::FREE,
                'price'             => 0,
                'features'          => json_encode(['Accès limité', 'Support email']),
                'duration_days'     => 0,
                'discount_percent'  => 0,
                'discount_expires_at' => null,
                'is_active'         => true,
            ],
            [
                'name'              => 'Plan Basic',
                'type'              => PlanTypeEnum::BASIC,
                'price'             => 9.99,
                'features'          => json_encode(['10 réservations', 'Support prioritaire']),
                'duration_days'     => 30,
                'discount_percent'  => 20,
                'discount_expires_at' => Carbon::now()->addDays(15),
                'is_active'         => true,
            ],
            [
                'name'              => 'Plan Premium',
                'type'              => PlanTypeEnum::PREMIUM,
                'price'             => 29.99,
                'features'          => json_encode(['Réservations illimitées', 'Support 24/7']),
                'duration_days'     => 30,
                'discount_percent'  => 0,
                'discount_expires_at' => null,
                'is_active'         => true,
            ],
            [
                'name'              => 'Plan Entreprise',
                'type'              => PlanTypeEnum::ENTREPRISE,
                'price'             => 99.99,
                'features'          => json_encode(['Accès API', 'Gestion multi-comptes']),
                'duration_days'     => 365,
                'discount_percent'  => 15,
                'discount_expires_at' => Carbon::now()->addMonth(),
                'is_active'         => true,
            ],
        ];

        foreach ($plans as $plan) {
            Plan::updateOrCreate(
                ['type' => $plan['type']],
                $plan
            );
        }
    }
}
