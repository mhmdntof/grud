<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RequestOrder extends Model
{
    protected $fillable = [
        'department_id',
        'requested_by',
        'manager_status',
        'warehouse_status',
        'rejection_reason'
    ];

    public function items()
    {
        return $this->hasMany(RequestOrderItem::class);
    }

    public function department()
    {
        return $this->belongsTo(Department::class);
    }

    public function requester()
    {
        return $this->belongsTo(User::class, 'requested_by');
    }
}