<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Product extends Model
{

protected $fillable = [
    'name',
    'code',
    'type',
    'total_quantity',
    'minimum_stock',
    'unit',
    'description'
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

}
