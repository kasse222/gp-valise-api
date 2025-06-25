<?php

namespace App\Http\Controllers\Api\V1;

use Illuminate\Routing\Controller;
use App\Http\Requests\Invitation\StoreInvitationRequest;
use App\Http\Requests\Invitation\AcceptInvitationRequest;
use App\Http\Resources\InvitationResource;
use App\Models\Invitation;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;

class InvitationController extends Controller
{
    /**
     * 🔍 Liste les invitations envoyées par l'utilisateur
     */
    public function index(Request $request)
    {
        $invitations = $request->user()->sentInvitations()->latest()->get();

        return InvitationResource::collection($invitations);
    }

    /**
     * ✉️ Envoie une nouvelle invitation
     */
    public function store(StoreInvitationRequest $request)
    {
        $this->authorize('create', Invitation::class);

        $invitation = Invitation::create([
            'sender_id'       => $request->user()->id,
            'recipient_email' => $request->validated('recipient_email'),
            'token'           => Str::uuid(),
        ]);

        // TODO : envoi d’email futur

        return (new InvitationResource($invitation))->response()->setStatusCode(201);
    }

    /**
     * ✅ Accepter une invitation (via token)
     */
    public function accept(AcceptInvitationRequest $request)
    {
        $invitation = Invitation::where('token', $request->validated('token'))
            ->whereNull('used_at')
            ->firstOrFail();

        DB::transaction(function () use ($invitation) {
            // TODO : ici tu pourrais créer le user (register), ou associer à un compte existant
            $invitation->update(['used_at' => now()]);
        });

        return response()->json(['message' => 'Invitation acceptée.']);
    }

    /**
     * 🗑 Supprimer une invitation (avant usage)
     */
    public function destroy(Invitation $invitation)
    {
        $this->authorize('delete', $invitation);

        if ($invitation->isUsed()) {
            return response()->json(['message' => 'Impossible de supprimer une invitation utilisée.'], 400);
        }

        $invitation->delete();

        return response()->json(['message' => 'Invitation supprimée.']);
    }
}
