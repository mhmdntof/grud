<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use App\Http\Resources\RequestOrderItemResource;
class RequestOrderResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        // تحديد أول مادة في الطلب (للعرض في القائمة)
        $firstItem = $this->items->first();

        return [
    'id' => (string) $this->id,
    'medicineName' => $firstItem?->product?->name ?? '---',
    'date' => $this->created_at?->format('Y-m-d\TH:i:s'),
    'quantity' => $this->items->sum('quantity'),
    'status' => $this->mapStatus($this->status),
    'rejectionReason' => $this->rejection_reason ?? '',
    'priority' => $this->request_type === 'urgent' ? 'urgent' : 'normal',
    'type' => $this->recurring_frequency ? 'دوري' : 'اعتيادي',
    'vendor' => '---',
    'isRecurring' => $this->recurring_frequency !== null,
    'recurringInterval' => $this->recurring_frequency,
    'receivedConfirmed' => $this->status === 'delivered',
    'receivedQuantity' => $this->items->sum('approved_quantity'),

    'isReadyForDelivery' => $this->status === 'ready_for_delivery',


    // تتبع الموافقات
    'managerStatus' => $this->manager_status ?? 'pending',
    'managerApprovedBy' => $this->when(
        $this->manager_approved_by,
        [
            'id' => (string) ($this->managerApprover?->id ?? ''),
            'name' => $this->managerApprover?->name ?? '',
        ]
    ),
    'managerApprovedAt' => $this->manager_approved_at?->format('Y-m-d\TH:i:s'),
    'managerRejectionReason' => $this->manager_rejection_reason,

    'warehouseStatus' => $this->warehouse_status ?? 'pending',
    'warehouseApprovedBy' => $this->when(
        $this->warehouse_approved_by,
        [
            'id' => (string) ($this->warehouseApprover?->id ?? ''),
            'name' => $this->warehouseApprover?->name ?? '',
        ]
    ),
    'warehouseApprovedAt' => $this->warehouse_approved_at?->format('Y-m-d\TH:i:s'),
    'warehouseRejectionReason' => $this->warehouse_rejection_reason,

    'department' => [
        'id' => (string) ($this->department?->id ?? ''),
        'name' => $this->department?->name ?? '',
    ],
    'createdBy' => [
        'id' => (string) ($this->requester?->id ?? ''),
        'name' => $this->requester?->name ?? '',
    ],
    'items' => RequestOrderItemResource::collection(
        $this->whenLoaded('items')
    ),
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
