<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class BatchResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $daysUntilExpiry = $this->expire_date
            ? now()->diffInDays($this->expire_date, false)
            : null;

        return [
            'id' => $this->id,
            'batch_number' => $this->batch_number,
            'quantity' => $this->quantity,
            'expire_date' => $this->expire_date?->format('Y-m-d'),
            'purchase_price' => (float) $this->purchase_price,
            'notes' => $this->notes,
            'expiry_status' => $this->getExpiryStatus($daysUntilExpiry),
            'days_until_expiry' => $daysUntilExpiry,
            'product' => new ProductResource($this->whenLoaded('product')),
            'supplier' => [
                'id' => $this->supplier?->id,
                'name' => $this->supplier?->name,
            ],
        ];
    }

    private function getExpiryStatus(?int $days): string
    {
        if ($days === null) return 'unknown';
        if ($days < 0) return 'expired';
        if ($days <= 30) return 'expiring_soon';
        return 'valid';
    }
}
