<?php

namespace App\Modules\Payment\Gateways;

use App\Models\Order;
use App\Modules\Payment\IPaymentGatewayInterface;
use App\Modules\Payment\PaymentGateway;

class StripePaymentGatewayGateway extends PaymentGateway implements IPaymentGatewayInterface
{
    public function initialize(): void
    {

    }

    public function processPayment(Order $order, string $currency): array
    {
        // TODO: Implement processPayment() method.
    }

    public function getPaymentStatus(array $data): array
    {
        // TODO: Implement getPaymentStatus() method.
    }

    public function webhookHandler($request): array
    {
        // TODO: Implement webhookHandler() method.
    }

    public function authenticate(): mixed
    {}

    public function cancelPaymentTransaction(array $data): void
    {}


}
