<?php

namespace App\Http\Controllers\Api\V1;

use App\Actions\Transaction\CreateTransaction;
use App\Actions\Transaction\RefundTransaction;
use App\Http\Requests\Transaction\RefundTransactionRequest;
use App\Http\Requests\Transaction\StoreTransactionRequest;
use App\Http\Resources\TransactionResource;
use App\Models\Transaction;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Routing\Controller;
use Symfony\Component\HttpFoundation\Response;

class TransactionController extends Controller
{
    use AuthorizesRequests;

    public function __construct()
    {
        $this->authorizeResource(Transaction::class, 'transaction');
    }


    public function index(Request $request): AnonymousResourceCollection
    {
        $transactions = $request->user()
            ->transactions()
            ->latest()
            ->paginate(10);

        return TransactionResource::collection($transactions);
    }


    public function show(Transaction $transaction): TransactionResource
    {
        return new TransactionResource($transaction);
    }


    public function store(
        StoreTransactionRequest $request,
        CreateTransaction $action
    ): JsonResponse {
        $transaction = $action->execute(
            $request->user(),
            $request->validated()
        );

        return (new TransactionResource($transaction))
            ->response()
            ->setStatusCode(Response::HTTP_CREATED);
    }


    public function refund(
        RefundTransactionRequest $request,
        Transaction $transaction,
        RefundTransaction $action
    ): JsonResponse {
        $this->authorize('refund', $transaction);

        $refund = $action->execute(
            $transaction,
            $request->validated('reason')
        );

        return (new TransactionResource($refund))
            ->response()
            ->setStatusCode(Response::HTTP_OK);
    }
}
