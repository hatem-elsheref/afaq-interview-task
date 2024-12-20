<?php

use App\Http\Controllers\CheckoutController;
use App\Http\Controllers\PaymentController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});



Route::get('/checkout', [CheckoutController::class, 'store']);

Route::get('/success' , [PaymentController::class, 'callbackHandler'])->name('payment.success');
Route::get('/cancel'  , [PaymentController::class, 'callbackHandler'])->name('payment.cancel');

Route::post('/{gateway}/webhook', [PaymentController::class, 'webhookHandler']);
