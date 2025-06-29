<?php

namespace App\Http\Controllers\Api\V1;

use App\Actions\Invitation\AcceptInvitation;
use App\Actions\Invitation\SendInvitation;
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

        $invitation = SendInvitation::execute($request->user(), $request->validated('recipient_email'));

        return (new InvitationResource($invitation))->response()->setStatusCode(201);
    }
    /**
     * ✅ Accepter une invitation (via token)
     */
    public function accept(AcceptInvitationRequest $request)
    {
        $invitation = AcceptInvitation::execute($request->validated('token'));

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
