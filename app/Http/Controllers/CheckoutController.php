<?php

namespace App\Http\Controllers;

use App\Http\Requests\PaymentRequest;
use App\Modules\Payment\IPaymentGatewayInterface;
use App\Modules\Payment\PaymentService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class CheckoutController extends Controller
{
    public function __construct(private readonly PaymentService $paymentService, IPaymentGatewayInterface $paymentGateway)
    {
        $this->paymentService->setGateway($paymentGateway);
    }

    public function store(Request $request): RedirectResponse
    {
        $resource = $this->paymentService->process($request);

        if ($resource['success']) {
            return redirect()->away($resource['link']);
        }

        return redirect()->back()->withErrors(['message' => $resource['message']]);
    }
}
