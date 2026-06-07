<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Requests\Kyc\StoreUploadRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;

class UploadController extends Controller
{
    public function store(StoreUploadRequest $request): JsonResponse
    {
        $path = $request->file('file')->store(
            'kyc/' . $request->user()->id,
            'private'
        );

        return response()->json(['path' => $path], 201);
    }
}
