<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class RequestOrderItemResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => (string) $this->id,
            'medicineName' => $this->product?->name ?? '---',
            'quantity' => $this->quantity,
            'approvedQuantity' => $this->approved_quantity,
            'unit' => $this->product?->unit ?? 'piece',
            'vendor' => '---',
        ];
    }
}
