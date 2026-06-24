<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class InventoryItem extends Model
{
    protected $fillable = [
        'inventory_session_id',
        'product_id',
        'batch_id',
        'system_quantity',
        'actual_quantity',
        'difference',
        'variance_type',
        'adjustment_notes'
    ];

    protected $casts = [
        'system_quantity' => 'integer',
        'actual_quantity' => 'integer',
        'difference' => 'integer',
    ];

    public function session()
    {
        return $this->belongsTo(InventorySession::class, 'inventory_session_id');
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function batch()
    {
        return $this->belongsTo(Batch::class);
    }

    // Helper methods
    public function isMatched(): bool
    {
        return $this->variance_type === 'match';
    }

    public function hasSurplus(): bool
    {
        return $this->variance_type === 'surplus';
    }

    public function hasShortage(): bool
    {
        return $this->variance_type === 'shortage';
    }

    public function isDamaged(): bool
    {
        return $this->variance_type === 'damaged';
    }

    public function calculateVariance(): void
    {
        if ($this->actual_quantity === null) {
            $this->variance_type = 'match';
            $this->difference = 0;
            return;
        }

        $this->difference = $this->actual_quantity - $this->system_quantity;

        if ($this->difference === 0) {
            $this->variance_type = 'match';
        } elseif ($this->difference > 0) {
            $this->variance_type = 'surplus';
        } else {
            $this->variance_type = 'shortage';
        }
    }
}
