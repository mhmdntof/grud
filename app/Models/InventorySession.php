<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class InventorySession extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'name',
        'inventory_date',
        'created_by',
        'status',
        'notes',
        'approved_by',     // ✅ أضف هذا
        'approved_at',     // ✅ أضف هذا (هذا هو الحل!)
    ];

    protected $casts = [
        'inventory_date' => 'date',
        'approved_at' => 'datetime',  // ✅ تأكد من وجود هذا
    ];

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function approver()
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function items()
    {
        return $this->hasMany(InventoryItem::class);
    }

    public function adjustments()
    {
        return $this->hasMany(InventoryAdjustment::class);
    }

    // Scopes
    public function scopeDraft($query)
    {
        return $query->where('status', 'draft');
    }

    public function scopeInProgress($query)
    {
        return $query->where('status', 'in_progress');
    }

    public function scopeCompleted($query)
    {
        return $query->where('status', 'completed');
    }

    public function scopeApproved($query)
    {
        return $query->where('status', 'approved');
    }

    public function scopeForDate($query, $date)
    {
        return $query->whereDate('inventory_date', $date);
    }
}
