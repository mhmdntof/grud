<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RequestOrderItem extends Model
{
    protected $fillable = [
        'request_order_id',
        'product_id',
        'quantity',
        'approved_quantity'
    ];

    public function requestOrder()
    {
        return $this->belongsTo(RequestOrder::class);
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }
}
