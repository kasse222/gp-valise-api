<?php

namespace App\Http\Controllers\Api\V1;

use Illuminate\Routing\Controller;
use App\Http\Requests\Transaction\StoreTransactionRequest;
use App\Http\Resources\TransactionResource;
use App\Models\Transaction;
use App\Services\TransactionService;
use Illuminate\Http\Request;

class TransactionController extends Controller
{
    /**
     * 📄 Lister les transactions de l’utilisateur
     */
    public function index(Request $request)
    {
        $transactions = $request->user()->transactions()->latest()->paginate(10);
        return TransactionResource::collection($transactions);
    }

    /**
     * 🔍 Détails d’une transaction
     */
    public function show(Transaction $transaction)
    {
        $this->authorize('view', $transaction);

        return new TransactionResource($transaction);
    }

    /**
     * ➕ Créer une transaction (ex : dépôt ou paiement)
     */
    public function store(StoreTransactionRequest $request, TransactionService $service)
    {
        $transaction = $service->create($request->user(), $request->validated());

        return response()->json(new TransactionResource($transaction), 201);
    }
}
