<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class InventoryAdjustment extends Model
{
    protected $fillable = [
        'inventory_session_id',
        'product_id',
        'type',
        'quantity',
        'approved_by',
        'approved_at',
        'notes'
    ];

    protected $casts = [
        'quantity' => 'integer',
        'approved_at' => 'datetime',
    ];

    public function session()
    {
        return $this->belongsTo(InventorySession::class, 'inventory_session_id');
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function approver()
    {
        return $this->belongsTo(User::class, 'approved_by');
    }
}
