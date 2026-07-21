<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PrescriptionMedicine extends Model
{
    protected $fillable = [
        'prescription_id',
        'medicine_name',
        'dosage',
        'quantity',
        'unit',
        'frequency',
        'instructions',
    ];

    protected $casts = [
        'quantity' => 'integer',
    ];

    public function prescription(): BelongsTo
    {
        return $this->belongsTo(Prescription::class);
    }
}
