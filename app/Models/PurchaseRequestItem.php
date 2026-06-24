<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class PurchaseRequestItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'purchase_request_id',
        'product_id',
        'requested_quantity',
        'approved_quantity',
        'reserved_quantity',
        'delivered_quantity',
        'received_quantity',
        'rejection_reason'
    ];

    protected $casts = [
        'requested_quantity' => 'integer',
        'approved_quantity' => 'integer',
        'reserved_quantity' => 'integer',
        'delivered_quantity' => 'integer',
        'received_quantity' => 'integer'
    ];

    public function purchaseRequest()
    {
        return $this->belongsTo(PurchaseRequest::class);
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }
}
