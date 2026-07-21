<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class RecurringRequestOrderResource extends JsonResource
{
public function toArray(Request $request): array
{
    $firstItem = $this->items->first();

    return [
        'id' => (string) $this->id,
        'medicineName' => $firstItem?->product?->name ?? '---',
        'date' => $this->created_at?->format('Y-m-d\TH:i:s'),
        'quantity' => $this->items->sum('quantity'),
        'status' => $this->mapStatus($this->status),
        'rejectionReason' => $this->rejection_reason ?? '',
        'priority' => $this->request_type === 'urgent' ? 'urgent' : 'normal',

        // ✅ الحقول الجديدة للطلبات الدورية
        'isRecurring' => (bool) $this->is_recurring,
        'recurringInterval' => $this->recurring_frequency,
        'isTemplate' => (bool) $this->is_template,  // ← جديد
        'nextOccurrence' => $this->next_occurrence?->format('Y-m-d'),
        'isActive' => (bool) $this->is_active,

        'type' => $this->recurring_frequency ? 'دوري' : 'اعتيادي',
        'vendor' => '---',
        'receivedConfirmed' => $this->status === 'delivered',
        'receivedQuantity' => $this->items->sum('approved_quantity'),

        // ✅ عدد النسخ المولّدة
        'instancesCount' => $this->when(
            $this->relationLoaded('recurringChildren'),
            fn() => $this->recurringChildren->count()
        ),

        'department' => [
            'id' => (string) ($this->department?->id ?? ''),
            'name' => $this->department?->name ?? '',
        ],
        'createdBy' => [
            'id' => (string) ($this->requester?->id ?? ''),
            'name' => $this->requester?->name ?? '',
        ],
    ];
}

private function mapStatus(?string $status): string
{
    return match($status) {
        'pending' => 'inProgress',
        'in_progress' => 'inProgress',
        'ready_for_delivery' => 'inProgress',
        'delivered' => 'completed',
        'rejected' => 'rejected',
        'cancelled' => 'suspended',
        'active' => 'inProgress',  // ← حالة القالب
        null => 'inProgress',
        default => 'inProgress',
    };
}
}
