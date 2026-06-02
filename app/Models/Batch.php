<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Batch extends Model
{
    protected $fillable = [
        'product_id',
        'batch_number',
        'quantity',
        'expire_date',
        'purchase_price',
        'request-type'
    ];

    public function product()
    {
        return $this->belongsTo(Product::class);
    }
}