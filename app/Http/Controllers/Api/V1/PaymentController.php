<?php

namespace App\Http\Controllers\Api\V1;

use App\Actions\Payment\GetPaymentDetails;
use App\Actions\Payment\ListPayments;
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


    public function index(Request $request, ListPayments $action)
    {
        $payments = $action->execute($request->user());

        return PaymentResource::collection($payments);
    }


    public function show(Payment $payment, GetPaymentDetails $action)
    {
        $payment = $action->execute($payment);

        return new PaymentResource($payment);
    }
}
