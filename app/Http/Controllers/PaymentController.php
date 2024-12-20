<?php

namespace App\Http\Controllers;

use App\Modules\Payment\IPaymentGatewayInterface;
use App\Modules\Payment\PaymentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;

class PaymentController extends Controller
{
    public function __construct(private readonly PaymentService $paymentService, private readonly IPaymentGatewayInterface $paymentGateway)
    {
        $this->paymentService->setGateway($paymentGateway);
    }

    public function callbackHandler(Request $request): View
    {
       $resource = $this->paymentService->processCallback($request);

       return $resource['success']
           ? view('payment.success', $resource)
           : view('payment.failure');
    }
    public function webhookHandler(Request $request): JsonResponse
    {
       $resource = $this->paymentService->processWebhook($request);

       Log::info('Webhook response results : ' . json_encode($resource));

       return response()->json(['message' => 'Webhook handled successfully']);
    }
}
