<?php

namespace App\Modules\Payment;

use App\Enums\OrderStatus;
use App\Enums\TransactionStatus;
use App\Models\Transaction;
use Exception;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

abstract class PaymentGateway
{
    protected array $configurations = [];

    public function initialize(): void
    {
        $this->configurations = config('payment.gateways.' . static::GATEWAY);
    }

    public function markTransactionAsCompleted($transactionNumber, $gateway): array
    {
        try {
            DB::beginTransaction();

            $transaction = Transaction::query()
                ->where('transaction_number', $transactionNumber)
                ->where('payment_gateway', $gateway)
                ->withWhereHas('order')
                ->lockForUpdate()->firstOrFail();

            if (!$transaction)
                throw new Exception('Undefined payment');

            if ($transaction->status === TransactionStatus::Pending->value){
                $transaction->update(['status' => TransactionStatus::Approved->value]);

                $transaction->order->update([
                    'status' => OrderStatus::Paid->value
                ]);
            }

            DB::commit();

            return $this->paymentStatus($transaction->fresh());

        }catch (Exception $exception){
            Log::error($exception->getMessage());

            DB::rollBack();

            return [
                'success'   => false,
                'message'   => 'something went wrong',
            ];
        }
    }

    public function markTransactionAsFailed($transactionNumber, $gateway): array
    {
        try {
            DB::beginTransaction();

            $transaction = Transaction::query()
                ->where('transaction_number', $transactionNumber)
                ->where('payment_gateway', $gateway)
                ->withWhereHas('order')
                ->lockForUpdate()->firstOrFail();

            if (!$transaction)
                throw new Exception('Undefined payment');

            if ($transaction->status === TransactionStatus::Pending->value){
                $transaction->update(['status' => TransactionStatus::Rejected->value]);

                $transaction->order->update([
                    'status' => OrderStatus::Cancelled->value
                ]);
            }

            DB::commit();

            return $this->paymentStatus($transaction->fresh());

        }catch (Exception $exception){
            Log::error($exception->getMessage());

            DB::rollBack();

            return [
                'success'   => false,
                'message'   => 'something went wrong',
            ];
        }
    }

    protected function paymentStatus($transaction): array
    {
        return [
            'success'        => true,
            'status'         => $transaction->status,
            'is_paid'        => $transaction->is_paid,
            'transaction_id' => $transaction->id,
            'order_id'       => $transaction->order_id,
            'order_status'   => $transaction->order->status,
        ];
    }

    abstract protected function authenticate(): mixed;
    abstract protected function verifyWebhook($request): bool;
}
