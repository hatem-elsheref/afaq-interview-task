<?php

namespace App\Models;

use App\Enums\TransactionStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Transaction extends Model
{
    protected $fillable = ['order_id', 'transaction_number', 'payment_id', 'amount', 'status', 'payment_gateway', 'payment_method'];

    public function getIsPaidAttribute(): bool
    {
        return $this->status === TransactionStatus::Approved->value;
    }
    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class, 'order_id');
    }
}
