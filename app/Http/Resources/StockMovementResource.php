<?php
// app/Http/Resources/StockMovementResource.php
namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class StockMovementResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'type' => $this->type,
            'type_label' => $this->getTypeLabel(),
            'quantity' => $this->quantity,
            'notes' => $this->notes,
            'created_at' => $this->created_at?->format('Y-m-d H:i'),

            // المستخدم
            'user' => [
                'id' => $this->user?->id,
                'name' => $this->user?->name,
            ],

            // القسم (للـ out فقط)
            'department' => $this->when($this->department_id, [
                'id' => $this->department?->id,
                'name' => $this->department?->name,
            ]),

            // المنتج
            'product' => [
                'id' => $this->product?->id,
                'name' => $this->product?->name,
                'code' => $this->product?->code,
                'unit' => $this->product?->unit,
            ],

            // الدفعة (للـ in, out, damage)
            'batch' => $this->when($this->batch_id, [
                'id' => $this->batch?->id,
                'batch_number' => $this->batch?->batch_number,
                'expire_date' => $this->batch?->expire_date?->format('Y-m-d'),
            ]),

            // الطلب المرتبط (للـ out)
            'request' => $this->when($this->request_id, [
                'id' => $this->request?->id,
                'status' => $this->request?->status,
            ]),
        ];
    }

    private function getTypeLabel(): string
    {
        return match($this->type) {
            'in' => 'إدخال',
            'out' => 'إخراج',
            'damage' => 'إتلاف',
            'adjustment' => 'تعديل',
            default => $this->type,
        };
    }
}
