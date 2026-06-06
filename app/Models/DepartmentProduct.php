<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DepartmentProduct extends Model
{
    protected $fillable = ['department_id', 'product_id', 'quantity'];

    public function department()
    {
        return $this->belongsTo(Department::class);
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }
}

