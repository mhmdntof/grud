<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class RequestResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'type' => $this->type,
            'status' => $this->status,
            'notes' => $this->notes,

            // القسم
            'department' => [
                'id' => $this->department?->id,
                'name' => $this->department?->name,
            ],

            // رئيس القسم (صاحب الطلب)
            'requested_by' => [
                'id' => $this->user?->id,
                'name' => $this->user?->name,
            ],

            // المنتج
            'product' => [
                'id' => $this->product?->id,
                'name' => $this->product?->name,
                'code' => $this->product?->code,
                'unit' => $this->product?->unit,
            ],

            // الكميات
            'quantities' => [
                'requested' => $this->requested_quantity,
                'approved' => $this->approved_quantity,
                'delivered' => $this->delivered_quantity,
            ],

            // التواريخ
            'needed_by' => $this->needed_by?->format('Y-m-d'),
            'created_at' => $this->created_at?->format('Y-m-d H:i'),

            // الرفض (إن وجد)
            'rejection_reason' => $this->when(
                $this->status === 'rejected',
                $this->rejection_reason
            ),

            // التكرار (إن كان طلب متكرر)
            'recurring_frequency' => $this->when(
                $this->type === 'recurring',
                $this->recurring_frequency
            ),
        ];
    }
}
