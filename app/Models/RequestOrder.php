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
        'status',
        'request_frequency',

        'is_recurring',
        'recurring_frequency',
        'next_occurrence',
        'is_active',

        'parent_id',
        'is_template',
        'rejected_by',
    ];

    protected $casts = [
    'is_recurring' => 'boolean',
    'is_active' => 'boolean',
    'next_occurrence' => 'date',

    'is_template' => 'boolean',

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


public function recurringChildren()
{
    return $this->hasMany(RequestOrder::class, 'parent_id');
}

/**
 * النسخة لها قالب واحد
 */
public function recurringParent()
{
    return $this->belongsTo(RequestOrder::class, 'parent_id');
}



public function rejectedBy()
{
    return $this->belongsTo(User::class, 'rejected_by');
}

    }
