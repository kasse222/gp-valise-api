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
        $byCountry = match ($country) {
            'FR' => [PaymentMethodEnum::CARD, PaymentMethodEnum::BANK_TRANSFER, PaymentMethodEnum::CHEQUE],
            'SN' => [PaymentMethodEnum::MOBILE_MONEY, PaymentMethodEnum::CASH, PaymentMethodEnum::VIREMENT],
            default => PaymentMethodEnum::cases()
        };

        $byCurrency = match ($currency) {
            'USD' => [PaymentMethodEnum::BANK_TRANSFER, PaymentMethodEnum::BANK_TRANSFER],
            'EUR' => [PaymentMethodEnum::CARD, PaymentMethodEnum::BANK_TRANSFER],
            default => PaymentMethodEnum::cases()
        };

        $byPlan = match ($plan) {
            PlanTypeEnum::PREMIUM => array_diff(PaymentMethodEnum::cases(), [
                PaymentMethodEnum::CASH,
                PaymentMethodEnum::BANK_TRANSFER,
            ]),
            default => PaymentMethodEnum::cases()
        };

        return in_array($method, $byCountry, true)
            && in_array($method, $byCurrency, true)
            && in_array($method, $byPlan, true);
    }
}
