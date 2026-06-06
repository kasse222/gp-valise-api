<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Actions\Booking\OpenDispute;
use App\Actions\Dispute\AddDisputeMessage;
use App\Http\Requests\Dispute\OpenDisputeRequest;
use App\Http\Requests\Dispute\AddDisputeMessageRequest;
use App\Http\Resources\DisputeResource;
use App\Http\Resources\DisputeMessageResource;
use App\Models\Booking;
use App\Models\Dispute;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;

class DisputeController extends Controller
{
    use AuthorizesRequests;

    public function open(OpenDisputeRequest $request, Booking $booking, OpenDispute $action): JsonResponse
    {
        $this->authorize('view', $booking);

        $booking = $action->execute($booking, $request->user(), $request->validated('reason'));

        return response()->json([
            'message' => 'Litige ouvert avec succès.',
            'data'    => new DisputeResource($booking->dispute),
        ], 201);
    }

    public function show(Request $request, Dispute $dispute): JsonResponse
    {
        $this->authorize('view', $dispute);

        return response()->json([
            'data' => new DisputeResource($dispute->load(['messages.author', 'statusHistories'])),
        ]);
    }

    public function addMessage(AddDisputeMessageRequest $request, Dispute $dispute, AddDisputeMessage $action): JsonResponse
    {
        $this->authorize('addMessage', $dispute);

        $message = $action->execute(
            $dispute,
            $request->user(),
            $request->validated('body'),
            $request->validated('attachments', []),
        );

        return response()->json([
            'message' => 'Message ajouté.',
            'data'    => new DisputeMessageResource($message),
        ], 201);
    }

    public function messages(Request $request, Dispute $dispute): JsonResponse
    {
        $this->authorize('view', $dispute);

        return response()->json([
            'data' => DisputeMessageResource::collection(
                $dispute->messages()->with('author')->get()
            ),
        ]);
    }
}
