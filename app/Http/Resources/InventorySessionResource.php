<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class InventorySessionResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'inventory_date' => $this->inventory_date?->format('Y-m-d'),
            'status' => $this->status,
            'status_label' => $this->getStatusLabel(),
            'notes' => $this->notes,
            'created_at' => $this->created_at?->format('Y-m-d H:i'),
            'approved_at' => $this->approved_at?->format('Y-m-d H:i'),

            'creator' => [
                'id' => $this->creator?->id,
                'name' => $this->creator?->name,
            ],

            'approver' => $this->when($this->approved_by, [
                'id' => $this->approved_by,
                'name' => $this->approver?->name ?? 'Unknown',
                'approved_at' => $this->approved_at?->format('Y-m-d H:i'),
            ]),

            'statistics' => $this->when($this->relationLoaded('items'), function () {
                return [
                    'total_items' => $this->items->count(),
                    'matched' => $this->items->where('variance_type', 'match')->count(),
                    'surplus' => $this->items->where('variance_type', 'surplus')->count(),
                    'shortage' => $this->items->where('variance_type', 'shortage')->count(),
                    'damaged' => $this->items->where('variance_type', 'damaged')->count(),
                ];
            }),
        ];
    }

    private function getStatusLabel(): string
    {
        return match($this->status) {
            'draft' => 'مسودة',
            'in_progress' => 'قيد التنفيذ',
            'completed' => 'مكتمل',
            'approved' => 'معتمد',
            default => $this->status,
        };
    }
}
