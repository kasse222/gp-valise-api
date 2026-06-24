<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Actions\Traveler\ComputeTravelerEarnings;
use App\Enums\CurrencyEnum;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;

class EarningsController extends Controller
{
    /**
     * GET /v1/me/earnings
     *
     * { "data": [{ "currency": "XOF", "escrow": 40000, "pending": 12000, "paid": 30000 }] }
     * Montants en minor units.
     */
    public function index(Request $request, ComputeTravelerEarnings $action): JsonResponse
    {
        $buckets = $action->execute($request->user());

        $data = [];
        foreach ($buckets as $code => $amounts) {
            $data[] = [
                'currency'       => $code,
                'currency_label' => CurrencyEnum::from($code)->name,
                'escrow'         => $amounts['escrow'],
                'pending'        => $amounts['pending'],
                'paid'           => $amounts['paid'],
            ];
        }

        return response()->json(['data' => $data]);
    }
}
