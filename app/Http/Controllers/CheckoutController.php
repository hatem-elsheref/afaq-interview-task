<?php

namespace App\Http\Controllers;

use App\Http\Requests\PaymentRequest;
use App\Modules\Payment\IPaymentGatewayInterface;
use App\Services\PaymentService;
use Illuminate\Http\RedirectResponse;

class CheckoutController extends Controller
{
    public function __construct(private readonly PaymentService $paymentService, IPaymentGatewayInterface $paymentGateway)
    {
        $this->paymentService->setGateway($paymentGateway);
    }

    public function store(PaymentRequest $request): RedirectResponse
    {
        $resource = $this->paymentService->process($request);

        if ($resource['success']) {
            return redirect()->away($resource['link']);
        }

        return redirect()->back()->withErrors(['message' => $resource['message']]);
    }
}
