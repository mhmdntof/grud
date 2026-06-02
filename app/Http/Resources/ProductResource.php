<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProductResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'code' => $this->code,
            'type' => $this->type,
            'total_quantity' => $this->total_quantity,
            'minimum_stock' => $this->minimum_stock,
            'unit' => $this->unit,
            'description' => $this->description,

            // التنبيهات الذكية
            'alerts' => [
                'is_low_stock' => $this->total_quantity <= $this->minimum_stock && $this->minimum_stock > 0,
                'is_out_of_stock' => $this->total_quantity === 0,
            ],

            // الدفعات النشطة
            'batches' => BatchResource::collection(
                $this->whenLoaded('batches', function () {
                    return $this->batches->where('quantity', '>', 0);
                })
            ),

            // الموردين
            'suppliers' => SupplierResource::collection(
                $this->whenLoaded('suppliers')
            ),
        ];
    }
}
