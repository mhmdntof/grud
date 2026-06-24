<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class InventoryItemResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'system_quantity' => $this->system_quantity,
            'actual_quantity' => $this->actual_quantity,
            'difference' => $this->difference,
            'variance_type' => $this->variance_type,
            'variance_label' => $this->getVarianceLabel(),
            'adjustment_notes' => $this->adjustment_notes,

            'product' => [
                'id' => $this->product?->id,
                'name' => $this->product?->name,
                'code' => $this->product?->code,
                'unit' => $this->product?->unit,
            ],

            'batch' => $this->when($this->batch_id, [
                'id' => $this->batch?->id,
                'batch_number' => $this->batch?->batch_number,
                'expire_date' => $this->batch?->expire_date?->format('Y-m-d'),
            ]),
        ];
    }

    private function getVarianceLabel(): string
    {
        return match($this->variance_type) {
            'match' => 'مطابق',
            'surplus' => 'فائض',
            'shortage' => 'عجز',
            'damaged' => 'تالف',
            default => $this->variance_type,
        };
    }
}
