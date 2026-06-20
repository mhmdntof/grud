<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RequestOrderItem extends Model
{
    protected $fillable = [
        'request_order_id',
        'product_id',
        'requested_quantity',
        'approved_quantity',
        'delivered_quantity',
        'rejection_reason'
    ];

    protected $casts = [
        'requested_quantity' => 'integer',
        'approved_quantity' => 'integer',
        'delivered_quantity' => 'integer',
    ];

    public function requestOrder()
    {
        return $this->belongsTo(RequestOrder::class);
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    // Helper methods
    public function isFullyApproved(): bool
    {
        return $this->approved_quantity >= $this->requested_quantity;
    }

    public function isPartiallyApproved(): bool
    {
        return $this->approved_quantity > 0 && $this->approved_quantity < $this->requested_quantity;
    }

    public function isRejected(): bool
    {
        return $this->approved_quantity === 0 || $this->approved_quantity === null;
    }
}
