<?php

namespace App\Modules\Payment;

use Illuminate\Support\ServiceProvider;

class PaymentServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->singleton(IPaymentGatewayInterface::class, function () {
           $defaultGateway = config('payment.default_gateway');

           $gateway = new (PaymentFactory::create($defaultGateway));

           $gateway->initialize();

           return $gateway;
        });

        $this->app->bind(PaymentService::class, function () {
           return new PaymentService(new OrderService());
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
    }
}
