<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\HasMany;
//use Illuminate\Database\Eloquent\Relations\BelongsToMany;
class Batch extends Model
{

    use SoftDeletes;

    protected $fillable = ['product_id', 'supplier_id',
        'batch_number', 'quantity', 'expire_date', 'purchase_price', 'notes','request-type'];

    protected $casts = [
        'expire_date' => 'date',
        'purchase_price' => 'decimal:2',
        'quantity' => 'integer',
    ];


    public function product()
{
    return $this->belongsTo(Product::class);
}

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class);
    }

    public function stockMovements(): HasMany
    {
        return $this->hasMany(StockMovement::class);
    }
}



