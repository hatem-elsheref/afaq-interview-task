<?php

namespace App\Modules\Payment\Gateways;

use App\Jobs\CancelPendingTransaction;
use App\Models\Order;
use App\Models\Transaction;
use App\Modules\Payment\IPaymentGatewayInterface;
use App\Modules\Payment\PaymentGateway;
use Exception;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Stripe\Checkout\Session;
use Stripe\PaymentIntent;
use Stripe\Stripe;
use Stripe\Webhook;

class StripePaymentGatewayGateway extends PaymentGateway implements IPaymentGatewayInterface
{
    const GATEWAY = 'stripe';

    public function processPayment(Order $order, string $currency): array
    {
        try {
            DB::beginTransaction();

            $this->authenticate();

            $items = [];

            foreach ($order->fresh('items')->items as $product) {
                $items[] = [
                    'price_data' => [
                        'currency' => $currency,
                        'product_data' => [
                            'name' => $product->product->name,
                        ],
                        'unit_amount' => $product->amount * 100,
                    ],
                    'quantity' => $product->quantity,
                ];
            }

            $transaction = Transaction::query()->create([
                'order_id'           => $order->id,
                'amount'             => $order->amount,
                'payment_gateway'    => self::GATEWAY,
                'payment_method'     => null
            ]);

            $session = Session::create([
                'payment_method_types' => ['card'],
                'line_items'           => $items,
                'mode'                 => 'payment',
                'success_url'          => route('payment.success', ['provider' => self::GATEWAY, 'transaction_id' => $transaction->id]),
                'cancel_url'           => route('payment.cancel' , ['provider' => self::GATEWAY, 'transaction_id' => $transaction->id]),
            ]);

            $transaction->update([
                'transaction_number' => $session->id,
            ]);

            DB::commit();

            Log::debug($session->url);
            return [
                'success' => true,
                'link'    => $session->url
            ];

        }catch (Exception $exception){

            Log::error($exception->getMessage());

            DB::rollBack();

            return [
                'success' => false,
                'message' => 'Something went wrong'
            ];
        }
    }

    public function getPaymentStatus(array $data): array
    {
        try {
            $this->authenticate();

            $transaction = Transaction::query()->findOrFail($data['transaction_id']);

            $session = Session::retrieve($transaction->transaction_number);

            if($session->payment_intent){
                $payment_intent = PaymentIntent::retrieve($session->payment_intent);

                return $payment_intent->status == 'succeeded'
                    ? $this->markTransactionAsCompleted($transaction->transaction_number, self::GATEWAY)
                    : $this->markTransactionAsFailed($transaction->transaction_number, self::GATEWAY);
            }

            if (!$transaction->is_notified) {

                $transaction->update(['is_notified' => true]);

                CancelPendingTransaction::dispatch($data)->delay(now()->addDay());
            }

        }catch (Exception $exception){
            Log::error($exception->getMessage());
        }

        return [
            'success' => false,
            'message' => 'Something went wrong'
        ];
    }

    public function webhookHandler($request): array
    {
        if ($this->verifyWebhook($request)) {
            if ($request->type == 'checkout.session.completed') {

                $this->markTransactionAsCompleted(data_get($request->all(), 'data.object.id'), self::GATEWAY);

                return ['success' => true];
            }
        }
        return ['success' => false];
    }

    public function authenticate(): mixed
    {
        Stripe::setApiKey($this->configurations['secret_key']);

        return null;
    }

    public function cancelPaymentTransaction(array $data): void
    {
        $this->markTransactionAsFailed($data['transaction_id'], self::GATEWAY);
    }

    protected function verifyWebhook($request): bool
    {
        try {
            $this->authenticate();

            $payload = file_get_contents('php://input');

            $signature = trim($request->header('STRIPE_SIGNATURE'));

            if (empty($signature)) {
                return false;
            }

            $elements = explode(',', $signature);

            $data = [];

            foreach ($elements as $element) {
                $parts = explode('=', $element);
                $data[$parts[0]] = $parts[1];
            }

            if (!isset($data['t'], $data['v1']) || (time() - (int) $data['t'] > 60000)) {
                return false;
            }

            $signedPayload = $data['t'] . '.' . $payload;

            $expectedMessage = hash_hmac('sha256', $signedPayload, $this->configurations['webhook_secret']);

            return hash_equals($data['v1'], $expectedMessage);
        } catch(Exception $exception) {
            Log::error($exception->getMessage());

            return false;
        }
    }
    protected function getWebhookEvent($request): array
    {
        try {
            $this->authenticate();

            $payload = file_get_contents('php://input');

            $signature = trim($request->header('STRIPE_SIGNATURE'));

            if (empty($signature)) {
                return [];
            }

            $event = Webhook::constructEvent($payload, $signature, $this->configurations['webhook_secret']);

            return [
                'is_valid' => true,
                'event'    => $event
            ];
        } catch(Exception $exception) {
            Log::error($exception->getMessage());

            return [];
        }
    }

}
