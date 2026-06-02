<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Requests\WaitlistEmail\WaitlistEmailRequest;
use App\Models\WaitlistEmail;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;

class WaitlistEmailController extends Controller
{
    public function store(WaitlistEmailRequest $request): JsonResponse
    {
        WaitlistEmail::create([
            ...$request->validated(),
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
        ]);

        return response()->json([
            'message' => 'Inscription enregistrée avec succès.',
        ], 201);
    }
}
