<?php

namespace App\Modules\Payment;

use App\Enums\OrderStatus;
use App\Models\Order;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;

class OrderService
{
    public function __construct(private float $totalPrice = 0, private array $items = [], private ?User $customer = null){}

    public function addItem($product, $quantity) :self
    {
        $this->items[] = [
            'product_id' => $product->id,
            'name'       => $product->name,
            'amount'     => $product->price,
            'quantity'   => $quantity,
        ];

        return $this;
    }

    public function setTotalPrice($price): self
    {
        $this->totalPrice = $price;
        return $this;
    }

    public function setCustomer(User $customer): self
    {
        $this->customer = $customer;
        return $this;
    }

    public function create(): ?Model
    {
        $order = Order::query()->create([
            'amount'    => $this->totalPrice,
            'user_id'   => $this->customer->id,
            'status'    => OrderStatus::Pending->value
        ]);

        foreach ($this->items as $item){
            $order->items()->create([
                'product_id' => $item['product_id'],
                'amount'     => $item['amount'],
                'quantity'   => $item['quantity'],
            ]);
        }

        return $order ?? null;
    }
}
