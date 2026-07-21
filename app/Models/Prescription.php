<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Prescription extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'patient_name',
        'patient_age',
        'patient_gender',
        'doctor_name',
        'medical_condition',
        'notes',
        'status',
        'rejection_reason',
        'department_id',
        'created_by',
        'approved_by',
    ];

    protected $casts = [
        'patient_age' => 'integer',
    ];

    // العلاقات
    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function medicines(): HasMany
    {
        return $this->hasMany(PrescriptionMedicine::class);
    }

    // Scopes للفلترة
    public function scopeNew($query)
    {
        return $query->where('status', 'new');
    }


    public function scopeProcessed($query)
    {
        return $query->where('status', 'processed');
    }

    public function scopeRejected($query)
    {
        return $query->where('status', 'rejected');
    }
}
