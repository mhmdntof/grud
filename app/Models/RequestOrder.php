<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RequestOrder extends Model
{
    protected $fillable = [
        'department_id',
        'requested_by',
        'request_type',
        'manager_status',
        'warehouse_status',
        'rejection_reason',
        'notes',
        'recurring_frequency'
    ];

    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
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

    // Scopes مفيدة
    public function scopePendingManager($query)
    {
        return $query->where('manager_status', 'pending');
    }

    public function scopePendingWarehouse($query)
    {
        return $query->where('manager_status', 'approved')
                     ->where('warehouse_status', 'pending');
    }

    public function scopeApproved($query)
    {
        return $query->where('warehouse_status', 'approved');
    }
}
