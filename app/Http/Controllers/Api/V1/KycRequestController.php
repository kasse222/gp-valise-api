<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Actions\Kyc\SubmitKycRequest;
use App\Http\Requests\Kyc\StoreKycRequestRequest;
use App\Http\Resources\KycRequestResource;
use App\Models\KycRequest;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;

class KycRequestController extends Controller
{
    use AuthorizesRequests;

    public function show(Request $request): JsonResponse
    {
        $kyc = KycRequest::query()
            ->where('user_id', $request->user()->id)
            ->with('reviewer')
            ->first();

        if (! $kyc) {
            return response()->json(['data' => null], 200);
        }

        return response()->json(['data' => new KycRequestResource($kyc)]);
    }

    public function store(StoreKycRequestRequest $request, SubmitKycRequest $action): JsonResponse
    {
        $this->authorize('create', KycRequest::class);

        $kyc = $action->execute($request->user(), $request->validated());

        return response()->json([
            'message' => 'Demande KYC soumise avec succès.',
            'data'    => new KycRequestResource($kyc),
        ], 201);
    }
}
