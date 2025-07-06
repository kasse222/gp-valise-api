<?php

namespace App\Http\Controllers\Api\V1;

use App\Enums\CurrencyEnum;
use App\Enums\PaymentMethodEnum;
use App\Enums\TransactionStatusEnum;
use App\Models\Transaction;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Symfony\Component\HttpFoundation\Response;
use App\Services\TransactionService;
use App\Http\Resources\TransactionResource;
use App\Http\Requests\Transaction\StoreTransactionRequest;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\JsonResponse;

class TransactionController extends Controller
{
    use AuthorizesRequests;

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
     * 🔍 Voir les détails d’une transaction
     */
    public function show(Transaction $transaction): TransactionResource
    {
        $this->authorize('view', $transaction);

        return new TransactionResource($transaction);
    }

    /**
     * ➕ Créer une nouvelle transaction
     */
    public function store(StoreTransactionRequest $request, TransactionService $service): JsonResponse
    {
        $this->authorize('create', Transaction::class);
        if (!Auth::user()?->verified_user) {
            abort(403, 'Votre compte n’est pas encore vérifié.');
        }

        $transaction = Transaction::create([
            'user_id'     => Auth::id(),
            'booking_id'  => $request->booking_id,
            'amount'      => $request->amount,
            'currency'    => CurrencyEnum::from($request->currency),
            'method'      => PaymentMethodEnum::from($request->method),
            'status'      => TransactionStatusEnum::from($request->status),
        ]);

        return (new TransactionResource($transaction))
            ->response()
            ->setStatusCode(Response::HTTP_CREATED); // ✅ force 201 sans casser le format
    }


    /**
     * 💸 Demander un remboursement
     */
    public function refund(Transaction $transaction, Request $request, TransactionService $service): \Illuminate\Http\JsonResponse
    {
        $this->authorize('refund', $transaction);

        $success = $service->refund($transaction);

        return response()->json([
            'message' => $success
                ? 'Transaction remboursée avec succès.'
                : 'Échec du remboursement.',
        ], $success ? Response::HTTP_OK : Response::HTTP_UNPROCESSABLE_ENTITY);
    }
}
