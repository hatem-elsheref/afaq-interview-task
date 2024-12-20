<?php

namespace App\Modules\Payment;

use App\Models\Product;
use Exception;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class PaymentService
{
    private IPaymentGatewayInterface $paymentGateway;

    public function __construct(private OrderService $orderService){}

    public function setGateway($paymentGateway): void
    {
        $this->paymentGateway = $paymentGateway;
    }

    public function process($request) :array
    {
        auth()->loginUsingId(1);

        $customer = $request->user();

        //$productItems = $request->validated();
        $productItems = [
            'products' => [
                [
                    'product_id' => 1,
                    'quantity'   => 1,
                ],
                [
                    'product_id' => 3,
                    'quantity'   => 5,
                ]
            ]
        ];

        $totalPrice = 0;

        $currentCurrency = $request->session()->get('currency', config('payment.currency'));

        try {
            DB::beginTransaction();

            $products = Product::query()->select('id', 'name', 'description', 'price')
                ->whereIn('id', array_column($productItems['products'], 'product_id'))->get();

            foreach ($products as $index => $product) {
                $quantity = data_get($productItems, "products.$index.quantity");

                $this->orderService->addItem($product, $quantity);

                $totalPrice += $quantity * max($product->price, 0);
            }

            $order = $this->orderService
                ->setTotalPrice($totalPrice)
                ->setCustomer($customer)
                ->create();

            DB::commit();

            return $this->paymentGateway->processPayment($order, $currentCurrency);
        }catch (Exception $exception){

            Log::error($exception->getMessage());

            DB::rollBack();

            return [
                'success' => false,
                'message' => 'Invalid operation, please try again.'
            ];
        }
    }

    public function processCallback($request) :array
    {
        return $this->paymentGateway->getPaymentStatus($request->query());
    }

    public function processWebhook($request) :array
    {
        return $this->paymentGateway->webhookHandler($request);
    }

}
