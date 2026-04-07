<?php

namespace App\Http\Controllers\Api\V1;

use App\Actions\Payment\HandlePaymentWebhook;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;

class WebhookController extends Controller
{
    public function __invoke(Request $request, HandlePaymentWebhook $action)
    {
        $action->execute(
            $request->all(),
            $request->getContent() // 🔥 important
        );

        return response()->json(['status' => 'ok']);
    }
}
