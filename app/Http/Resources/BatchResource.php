<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class BatchResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'batch_number' => $this->batch_number,
            'quantity' => $this->quantity,
            'expire_date' => $this->expire_date?->format('Y-m-d'),
            'purchase_price' => (float) $this->purchase_price,
            'notes' => $this->notes,
            'product' => new ProductResource($this->whenLoaded('product')),
            'supplier' => [
                'id' => $this->supplier?->id,
                'name' => $this->supplier?->name,
            ],
            // نُخفي: created_at, updated_at, deleted_at, product_id, supplier_id
        ];
    }
}
