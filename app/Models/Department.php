<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Department extends Model
{
    public function products()
{
    return $this->belongsToMany(
        Product::class,
        'department_products'
    )->withPivot('quantity');
}
}
