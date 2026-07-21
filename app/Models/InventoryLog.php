<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class InventoryLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'action',
        'product_id',
        'quantity',
        'reference_type',
        'reference_id',
        'data',
        'user_id',
    ];

    protected $casts = [
        'data' => 'array',
    ];

    // العلاقات

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
