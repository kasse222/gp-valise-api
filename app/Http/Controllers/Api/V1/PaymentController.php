<?php

namespace App\Http\Controllers\Api\V1;

use App\Actions\Payment\CreatePayment;
use App\Actions\Payment\GetPaymentDetails;
use App\Actions\Payment\ListPayments;
use App\Actions\Payment\UpdatePayment;
use App\Http\Requests\Payment\StorePaymentRequest;
use App\Http\Requests\Payment\UpdatePaymentRequest;
use App\Http\Resources\PaymentResource;
use App\Models\Payment;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;

class PaymentController extends Controller
{
    use AuthorizesRequests;

    public function __construct()
    {
        $this->authorizeResource(Payment::class, 'payment');
    }

    /**
     * 💳 Lister les paiements de l’utilisateur connecté
     */
    public function index(Request $request, ListPayments $action)
    {
        $payments = $action->execute($request->user());

        return PaymentResource::collection($payments);
    }

    /**
     * 💰 Créer un nouveau paiement
     */
    public function store(StorePaymentRequest $request, CreatePayment $action)
    {
        $payment = $action->execute($request->user(), $request->validated());

        return PaymentResource::make($payment)
            ->response()
            ->setStatusCode(201);
    }

    /**
     * 🔍 Afficher un paiement spécifique
     */
    public function show(Payment $payment, GetPaymentDetails $action)
    {
        $payment = $action->execute($payment);

        return new PaymentResource($payment);
    }

    /**
     * ✏️ Modifier un paiement
     */
    public function update(UpdatePaymentRequest $request, Payment $payment, UpdatePayment $action)
    {
        $payment = $action->execute($payment, $request->validated());

        return new PaymentResource($payment);
    }

    /**
     * ❌ Supprimer un paiement
     */
    public function destroy(Payment $payment)
    {
        $payment->delete();

        return response()->json([
            'message' => 'Paiement supprimé.',
        ]);
    }
}
