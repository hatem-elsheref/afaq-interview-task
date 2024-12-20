<?php

namespace App\Modules\Payment\Gateways;

use App\Jobs\CancelPendingTransaction;
use App\Models\Order;
use App\Models\Transaction;
use App\Modules\Payment\IPaymentGatewayInterface;
use App\Modules\Payment\PaymentGateway;
use Exception;
use Illuminate\Support\Facades\Log;
use Srmklive\PayPal\Services\PayPal;

class PaypalPaymentGatewayGateway extends PaymentGateway implements IPaymentGatewayInterface
{
    const GATEWAY = 'paypal';
    public function processPayment(Order $order, string $currency): array
    {
        try {
           $provider = $this->authenticate();

            $payment = $provider->createOrder([
                'intent' => 'CAPTURE',
                'application_context' => [
                    'return_url' => route('payment.success', ['provider' => 'paypal']),
                    'cancel_url' => route('payment.cancel', ['provider' => 'paypal']),
                ],
                'purchase_units' => [
                    [
                        'amount' => [
                            'currency_code' => $currency,
                            'value' => $order->amount
                        ]
                    ]
                ]
            ]);

            if (isset($payment['id'], $payment['status']) && $payment['status'] == 'CREATED') {
                Transaction::query()->create([
                    'order_id'           => $order->id,
                    'amount'             => $order->amount,
                    'transaction_number' => $payment['id'],
                    'payment_gateway'    => self::GATEWAY,
                    'payment_method'     => null
                ]);

                return [
                    'success' => true,
                    'link'    => $payment['links'][1]['href']
                ];
            }

            throw new Exception('Payment failed');
        }catch (Exception $exception){
            Log::error($exception->getMessage());

            return [
                'success' => false,
                'message' => 'Something went wrong'
            ];
        }
    }

    public function getPaymentStatus(array $data): array
    {
        try {
            $provider = $this->authenticate();

            $orderStatusResponse = $provider->showOrderDetails($data['token']);

            if (isset($orderStatusResponse['status'], $orderStatusResponse['id'])) {

                if ($orderStatusResponse['status'] === 'COMPLETED'){
                    $transaction = Transaction::query()
                        ->with('order')
                        ->where('transaction_number', $orderStatusResponse['id'])
                        ->first();

                    return $this->paymentStatus($transaction);
                }

                $captureStatusResponse = $provider->capturePaymentOrder($data['token']);

                if (isset($captureStatusResponse['status'], $captureStatusResponse['id'])) {
                    return $captureStatusResponse['status'] === 'COMPLETED'
                        ? $this->markTransactionAsCompleted($captureStatusResponse['id'], self::GATEWAY)
                        : $this->markTransactionAsFailed($captureStatusResponse['id'], self::GATEWAY);
                }

                if (isset($captureStatusResponse['status']) && $captureStatusResponse['status'] === 'CREATED') {
                    CancelPendingTransaction::dispatch($data, self::GATEWAY)->delay(now()->addDay());
                }

            }

            throw new Exception('Failed to get payment status');

        }catch (Exception $exception){
            Log::error($exception->getMessage());
        }

        return [
            'success' => false,
            'message' => 'Something went wrong'
        ];
    }

    public function cancelPaymentTransaction(array $data): void
    {
        $this->markTransactionAsFailed($data['token'], self::GATEWAY);
    }

    public function webhookHandler($request): array
    {
        if (!$this->verifyWebhook($request)) {
            return ['success' => false];
        }

        $event = $request->input('event_type');

        $data  = $request->input('resource');

        if ($event === 'PAYMENT.CAPTURE.COMPLETED') {
            $transactionNumber = $data['id'];

            return $this->markTransactionAsCompleted($transactionNumber, self::GATEWAY);

        }

        return ['success' => true];
    }

    protected function authenticate(): mixed
    {
        $provider = new PayPal();

        $provider->setApiCredentials($this->configurations);

        $token = $provider->getAccessToken();

        $provider->setAccessToken($token);

        return $provider;
    }

    protected function verifyWebhook($request): bool
    {
        $mode = $this->configurations['mode'];

        $verificationData = [
            'auth_algo'         => $request->header('PAYPAL-AUTH-ALGO'),
            'cert_url'          => $request->header('PAYPAL-CERT-URL'),
            'transmission_id'   => $request->header('PAYPAL-TRANSMISSION-ID'),
            'transmission_sig'  => $request->header('PAYPAL-TRANSMISSION-SIG'),
            'transmission_time' => $request->header('PAYPAL-TRANSMISSION-TIME'),
            'webhook_id'        => $this->configurations[$mode]['webhook_id'],
            'webhook_event'     => $request->all(),
        ];

        $provider = $this->authenticate();

        $response = $provider->verifyWebhookSignature($verificationData);

        return $response['verification_status'] === 'SUCCESS';
    }
}
