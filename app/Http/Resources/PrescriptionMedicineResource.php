<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PrescriptionMedicineResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => (string) $this->id,
            'medicineName' => $this->medicine_name,
            'dosage' => $this->dosage,
            'quantity' => $this->quantity,
            'unit' => $this->unit,
            'frequency' => $this->frequency,
            'instructions' => $this->instructions,
        ];
    }
}
