<?php

namespace App\Http\Controllers\Api\V1;

use App\Models\Transaction;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;

use App\Services\TransactionService;
use App\Http\Resources\TransactionResource;
use App\Http\Requests\Transaction\StoreTransactionRequest;

class TransactionController extends Controller
{
    use AuthorizesRequests;

    protected TransactionService $transactionService;

    public function __construct(TransactionService $transactionService)
    {
        // Active automatiquement les policies via les méthodes index/show/store/destroy...
        $this->authorizeResource(Transaction::class, 'transaction');
        $this->transactionService = $transactionService;
    }

    /**
     * 📄 Lister les transactions de l’utilisateur connecté
     */
    public function index(Request $request): \Illuminate\Http\Resources\Json\AnonymousResourceCollection
    {
        $this->authorize('viewAny', Transaction::class);

        $transactions = $request->user()
            ->transactions()
            ->latest()
            ->paginate(10);

        return TransactionResource::collection($transactions);
    }

    /**
     * 📄 Voir une transaction (chargement sécurisé du booking)
     */
    public function show(Transaction $transaction): TransactionResource
    {
        $currentUser = Auth::user();
        $transaction->loadMissing('booking');

        $ownerId = $transaction->booking?->user_id;
        $isOwner = $ownerId !== null && $ownerId === $currentUser->id;

        if (! $currentUser->isAdmin() && ! $isOwner) {
            abort(403);
        }

        return new TransactionResource($transaction);
    }

    /**
     * ➕ Créer une nouvelle transaction
     */
    public function store(StoreTransactionRequest $request): JsonResponse
    {
        $this->authorize('create', Transaction::class);

        if (!Auth::user()?->verified_user) {
            abort(Response::HTTP_FORBIDDEN, 'Votre compte n’est pas encore vérifié.');
        }

        $transaction = $this->transactionService->create(Auth::user(), $request->validated());

        return (new TransactionResource($transaction))
            ->response()
            ->setStatusCode(Response::HTTP_CREATED);
    }

    /**
     * 💸 Demander un remboursement
     */
    public function refund(Request $request, Transaction $transaction): JsonResponse
    {
        $currentUser = Auth::user();

        $transaction->loadMissing('booking');
        $ownerId = $transaction->booking?->user_id;
        $isOwner = $ownerId !== null && $ownerId === $currentUser->id;

        if (! $currentUser->isAdmin() && ! $isOwner) {
            abort(403);
        }

        $validated = $request->validate([
            'reason' => ['required', 'string', 'max:255'],
        ]);

        $transaction = $this->transactionService->refund(
            $transaction,
            $validated['reason']
        );

        return (new TransactionResource($transaction))
            ->response()
            ->setStatusCode(Response::HTTP_OK);
    }
}
