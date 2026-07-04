<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use App\Http\Resources\RequestOrderItemResource;

class RequestOrderResource extends JsonResource
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

        // ✅ الحقول الدورية
        'isRecurring' => (bool) $this->is_recurring,
        'recurringInterval' => $this->recurring_frequency,
        'isTemplate' => (bool) $this->is_template,

        // ✅ الحقول الجديدة للتمييز
        'isRecurringInstance' => $this->parent_id !== null,  // ← جديد!
        'parentTemplateId' => $this->parent_id ? (string) $this->parent_id : null,  // ← جديد!

        'type' => $this->recurring_frequency ? 'دوري' : 'اعتيادي',
        'vendor' => '---',
        'receivedConfirmed' => $this->status === 'delivered',
        'receivedQuantity' => $this->items->sum('approved_quantity'),

        'department' => [
            'id' => (string) ($this->department?->id ?? ''),
            'name' => $this->department?->name ?? '',
        ],
        'createdBy' => [
            'id' => (string) ($this->requester?->id ?? ''),
            'name' => $this->requester?->name ?? '',
        ],
        'items' => RequestOrderItemResource::collection($this->items),
    ];
}


    /**
     * تحويل حالة الطلب إلى صيغة Flutter
     */
private function mapStatus(?string $status): string
{
    return match($status) {
        'pending' => 'inProgress',
        'in_progress' => 'inProgress',
        'preparing' => 'inProgress',  // ← دمج مع التنفيذ
        'ready' => 'inProgress',      // ← دمج مع التنفيذ
        'rejected' => 'rejected',
        'delivered' => 'completed',   // ← Frontend يتوقع completed
        'delivery_rejected' => 'rejected',
        'cancelled' => 'suspended',

        null => 'inProgress',
        default => 'inProgress',
    };
}
}
