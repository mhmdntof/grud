<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;
class Product extends Model
{
    use SoftDeletes;

protected $fillable = [
    'name',
    'code',
    'type',
    'total_quantity',
    'minimum_stock',
    'unit',
    'description'
];

    protected $casts = [
        'total_quantity' => 'integer',
        'minimum_stock' => 'integer',
    ];


    public function batches()
{
    return $this->hasMany(Batch::class);
}
public function departments()
{
    return $this->belongsToMany(
        Department::class,
        'department_products'
    )->withPivot('quantity');
}

    public function suppliers(): BelongsToMany
    {
        return $this->belongsToMany(Supplier::class, 'product_supplier')
                    ->withPivot('notes', 'is_primary')
                    ->withTimestamps();
    }

    public function stockMovements(): HasMany
    {
        return $this->hasMany(StockMovement::class);
    }

    public function requests(): HasMany
    {
        return $this->hasMany(Request::class);
    }



}
