<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Order extends Model
{
    protected $fillable = ['user_id', 'amount', 'status'];

    public function customer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }
    public function transaction(): HasOne
    {
        return $this->hasOne(Transaction::class, 'order_id');
    }
    public function items(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }
}
