<?php

namespace App\Modules\Payment;

use App\Modules\Payment\Gateways\PaypalPaymentGatewayGateway;
use App\Modules\Payment\Gateways\StripePaymentGatewayGateway;

class PaymentFactory
{
    public static function create($gateway): string
    {
        return match ($gateway) {
            'stripe'  => StripePaymentGatewayGateway::class,
            'paypal'  => PaypalPaymentGatewayGateway::class,
            default   => PaymobPaymentGatewayGateway::class,
        };
    }
}
