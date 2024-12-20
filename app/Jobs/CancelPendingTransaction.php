<?php

namespace App\Jobs;

use App\Modules\Payment\IPaymentGatewayInterface;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class CancelPendingTransaction implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new job instance.
     */
    public function __construct(private readonly array $data){}

    /**
     * Execute the job.
     */
    public function handle(IPaymentGatewayInterface $paymentGateway): void
    {
        $resource = $paymentGateway->getPaymentStatus($this->data);

        if (!$resource['success']) {
            $paymentGateway->cancelPaymentTransaction($this->data);
        }
    }
}
