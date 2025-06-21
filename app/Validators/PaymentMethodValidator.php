<?php

namespace App\Validators;

use App\Enums\PaymentMethodEnum;
use App\Enums\PlanTypeEnum;

class PaymentMethodValidator
{
    public static function isAccepted(
        PaymentMethodEnum $method,
        string $country,
        string $currency,
        ?PlanTypeEnum $plan = null
    ): bool {
        // ✅ Pays
        $byCountry = match ($country) {
            'FR' => [PaymentMethodEnum::CARTE_BANCAIRE, PaymentMethodEnum::VIREMENT, PaymentMethodEnum::CHEQUE],
            'SN' => [PaymentMethodEnum::MOBILE_MONEY, PaymentMethodEnum::ESPECE, PaymentMethodEnum::VIREMENT],
            default => PaymentMethodEnum::cases()
        };

        // ✅ Devise
        $byCurrency = match ($currency) {
            'USD' => [PaymentMethodEnum::PAYPAL, PaymentMethodEnum::CRYPTO],
            'EUR' => [PaymentMethodEnum::CARTE_BANCAIRE, PaymentMethodEnum::VIREMENT],
            default => PaymentMethodEnum::cases()
        };

        // ✅ Plan (si fourni)
        $byPlan = match ($plan) {
            PlanTypeEnum::PREMIUM => array_diff(PaymentMethodEnum::cases(), [
                PaymentMethodEnum::ESPECE,
                PaymentMethodEnum::CHEQUE,
            ]),
            default => PaymentMethodEnum::cases()
        };

        return in_array($method, $byCountry, true)
            && in_array($method, $byCurrency, true)
            && in_array($method, $byPlan, true);
    }
}
