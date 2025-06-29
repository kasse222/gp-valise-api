<?php

namespace App\Http\Controllers\Api\V1;

use App\Actions\Payment\CreatePayment;
use App\Actions\Payment\UpdatePayment;
use App\Http\Resources\PaymentResource;
use Illuminate\Routing\Controller;
use App\Http\Requests\Payment\StorePaymentRequest;
use App\Http\Requests\Payment\UpdatePaymentRequest;
use App\Models\Payment;
use Illuminate\Http\Request;

class PaymentController extends Controller
{
    /**
     * 💳 Lister les paiements de l’utilisateur
     */
    public function index(Request $request)
    {
        $payments = $request->user()->payments()->latest()->paginate(10);

        return PaymentResource::collection($payments);
    }

    /**
     * 💰 Créer un paiement
     */
    public function store(StorePaymentRequest $request)
    {
        $payment = CreatePayment::execute($request->user(), $request->validated());

        return response()->json(new PaymentResource($payment), 201);
    }

    /**
     * 🔍 Afficher un paiement précis
     */
    public function show(Payment $payment)
    {
        $this->authorize('view', $payment);

        return new PaymentResource($payment);
    }

    /**
     * ✏️ Modifier un paiement (ex: méthode, statut...)
     */
    public function update(UpdatePaymentRequest $request, Payment $payment)
    {
        $this->authorize('update', $payment);

        $payment = UpdatePayment::execute($payment, $request->validated());

        return new PaymentResource($payment);
    }

    /**
     * ❌ Supprimer un paiement (ex: erreur, annulation...)
     */
    public function destroy(Payment $payment)
    {
        $this->authorize('delete', $payment);

        $payment->delete();

        return response()->json(['message' => 'Paiement supprimé.']);
    }
}
