<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProductSupplier extends Model
{
    protected $table = 'product_supplier';

    protected $fillable = [
        'product_id',
        'supplier_id',
        'notes',
        'is_primary',
    ];

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function supplier()
    {
        return $this->belongsTo(Supplier::class);
    }
}