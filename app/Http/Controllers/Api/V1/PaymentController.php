<?php

namespace App\Http\Controllers\Api\V1;

use App\Actions\Payment\CreatePayment;
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
        // 🔐 Active automatiquement authorize('view', $payment), etc.
        $this->authorizeResource(Payment::class, 'payment');
    }

    /**
     * 💳 Lister les paiements de l’utilisateur connecté
     */
    public function index(Request $request)
    {
        $payments = $request->user()
            ->payments()
            ->latest()
            ->paginate(10);

        return PaymentResource::collection($payments);
    }

    /**
     * 💰 Créer un nouveau paiement
     *
     * @authorize create
     */
    public function store(StorePaymentRequest $request)
    {
        $payment = CreatePayment::execute($request->user(), $request->validated());

        return PaymentResource::make($payment)
            ->response()
            ->setStatusCode(201);
    }

    /**
     * 🔍 Afficher un paiement spécifique
     *
     * @authorize view
     */
    public function show(Payment $payment)
    {
        return new PaymentResource($payment);
    }

    /**
     * ✏️ Modifier un paiement
     *
     * @authorize update
     */
    public function update(UpdatePaymentRequest $request, Payment $payment)
    {

        $payment = UpdatePayment::execute($payment, $request->validated());

        return new PaymentResource($payment);
    }

    /**
     * ❌ Supprimer un paiement
     *
     * @authorize delete
     */
    public function destroy(Payment $payment)
    {
        $payment->delete();

        return response()->json(['message' => 'Paiement supprimé.']);
    }
}
