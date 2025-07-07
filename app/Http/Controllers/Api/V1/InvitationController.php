<?php

namespace App\Http\Controllers\Api\V1;

use App\Models\Invitation;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;

use App\Actions\Invitation\AcceptInvitation;
use App\Actions\Invitation\SendInvitation;
use App\Http\Resources\InvitationResource;
use App\Http\Requests\Invitation\StoreInvitationRequest;
use App\Http\Requests\Invitation\AcceptInvitationRequest;

class InvitationController extends Controller
{
    use AuthorizesRequests;

    /**
     * 🔍 Liste les invitations envoyées par l’utilisateur connecté
     */
    public function index(Request $request)
    {
        $invitations = $request->user()
            ->sentInvitations()
            ->latest()
            ->get();

        return InvitationResource::collection($invitations);
    }

    /**
     * ✉️ Crée une nouvelle invitation
     */
    public function store(StoreInvitationRequest $request): JsonResponse
    {
        $this->authorize('create', Invitation::class);

        $invitation = SendInvitation::execute(
            sender: $request->user(),
            recipientEmail: $request->validated('recipient_email'),
            message: $request->input('message')
        );

        return (new InvitationResource($invitation))
            ->withCanSeeToken()
            ->response()
            ->setStatusCode(201);
    }

    /**
     * 🔎 Affiche les détails d’une invitation
     */
    public function show(Invitation $invitation): InvitationResource
    {
        $this->authorize('view', $invitation);

        return (new InvitationResource($invitation))
            ->withCanSeeToken(); // 🧠 Peut être optionnel selon rôle
    }

    /**
     * ✅ Accepte une invitation via token (user non connecté)
     */
    public function accept(AcceptInvitationRequest $request): JsonResponse
    {
        $invitation = AcceptInvitation::execute(
            $request->validated('token')
        );

        return response()->json([
            'message' => 'Invitation acceptée.',
            'invitation' => new InvitationResource($invitation),
        ]);
    }

    /**
     * 🗑 Supprime une invitation (si non utilisée)
     */
    public function destroy(Invitation $invitation): JsonResponse
    {
        $this->authorize('delete', $invitation);

        if ($invitation->isUsed()) {
            return response()->json([
                'message' => 'Impossible de supprimer une invitation déjà utilisée.',
            ], 400);
        }

        $invitation->delete();

        return response()->json([
            'message' => 'Invitation supprimée avec succès.',
        ]);
    }
}
