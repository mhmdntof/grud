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
        'request_type',
        'status',
        'request_frequency',
        'rejected_by',
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

public function user()
{
    return $this->belongsTo(User::class, 'requested_by');
}

public function rejectedBy()
{
    return $this->belongsTo(User::class, 'rejected_by');
}
 
    }
