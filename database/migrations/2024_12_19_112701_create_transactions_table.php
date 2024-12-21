<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained('orders');
            $table->string('transaction_number')->nullable();
            $table->string('payment_id')->nullable();
            $table->enum('status', ['pending', 'approved', 'rejected'])->default('pending');
            $table->float('amount')->default(0);
            $table->boolean('is_notified')->default(false);
            $table->string('payment_gateway');
            $table->string('payment_method')->nullable();
            $table->longText('meta')->nullable();
            $table->unique(['transaction_number', 'payment_gateway']);
            $table->unique(['payment_id', 'payment_gateway']);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('transactions');
    }
};
