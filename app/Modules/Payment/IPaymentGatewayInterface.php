<?php

namespace App\Modules\Payment;

use App\Models\Order;

interface IPaymentGatewayInterface
{
    public function initialize(): void;
    public function processPayment(Order $order, string $currency): array;
    public function getPaymentStatus(array $data): array;
    public function cancelPaymentTransaction(array $data): void;
    public function webhookHandler($request): array;
}
