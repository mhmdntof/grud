<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RequestOrder extends Model
{
    protected $fillable = [
        'department_id',
        'requested_by',
        'manager_status',
        'request_type',
        'warehouse_status',
        'rejection_reason',
        'request_type'
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