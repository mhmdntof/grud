<?php

namespace App\Services;

use App\Http\Resources\BatchResource;
use App\Http\Resources\ProductResource;
use App\Models\Batch;
use App\Models\Product;
use App\Models\Request;
use App\Models\StockMovement;
use Illuminate\Support\Facades\DB;

class WarehouseService
{
    public function stockIn(array $data, int $userId): array
    {
        return DB::transaction(function () use ($data, $userId) {
            $batch = Batch::create([
                'product_id' => $data['product_id'],
                'supplier_id' => $data['supplier_id'],
                'batch_number' => $data['batch_number'],
                'quantity' => $data['quantity'],
                'expire_date' => $data['expire_date'],
                'purchase_price' => $data['purchase_price'] ?? null,
                'notes' => $data['notes'] ?? null,
            ]);

            $product = Product::find($data['product_id']);
            $product->increment('total_quantity', $data['quantity']);

            StockMovement::create([
                'user_id' => $userId,
                'product_id' => $data['product_id'],
                'batch_id' => $batch->id,
                'type' => 'in',
                'quantity' => $data['quantity'],
                'notes' => $data['notes'] ?? null,
            ]);

            return [
                'batch' => new BatchResource($batch->load('product', 'supplier')),
                'product_total' => $product->fresh()->total_quantity,
            ];
        });
    }

    public function stockOut(array $data, int $userId): array
    {
        return DB::transaction(function () use ($data, $userId) {
            $product = Product::lockForUpdate()->findOrFail($data['product_id']);

            if ($product->total_quantity < $data['quantity']) {
                throw new \Exception('الكمية المطلوبة غير متوفرة في المخزون');
            }

            // إذا كان مرتبط بطلب → تحقق + تحديث
            $requestModel = null;
            if (!empty($data['request_id'])) {
                $requestModel = Request::lockForUpdate()->findOrFail($data['request_id']);

                if ($requestModel->product_id != $data['product_id']) {
                    throw new \Exception('المنتج لا يتطابق مع الطلب');
                }

                if ($requestModel->status !== 'approved') {
                    throw new \Exception('الطلب يجب أن يكون معتمداً أولاً');
                }
            }

            // FIFO: جلب الدفعات حسب أقرب تاريخ صلاحية
            $batches = Batch::where('product_id', $data['product_id'])
                ->where('quantity', '>', 0)
                ->orderBy('expire_date', 'asc')
                ->lockForUpdate()
                ->get();

            $remaining = $data['quantity'];
            $movements = [];

            foreach ($batches as $batch) {
                if ($remaining <= 0) {
                    break;
                }

                $take = min($remaining, $batch->quantity);

                $batch->decrement('quantity', $take);

                $movement = StockMovement::create([
                    'user_id' => $userId,
                    'department_id' => $data['department_id'],
                    'product_id' => $data['product_id'],
                    'batch_id' => $batch->id,
                    'request_id' => $data['request_id'] ?? null,
                    'type' => 'out',
                    'quantity' => $take,
                    'notes' => $data['notes'] ?? null,
                ]);

                $movements[] = $movement;
                $remaining -= $take;
            }

            $product->decrement('total_quantity', $data['quantity']);

            // تحديث الطلب إذا كان مرتبطاً
            if ($requestModel) {
                $requestModel->update([
                    'delivered_quantity' => $data['quantity'],
                    'status' => 'delivered',
                ]);
            }

            return [
                'product' => new ProductResource($product->fresh()),
                'movements' => $movements,
                'total_deducted' => $data['quantity'],
                'request' => $requestModel,
            ];
        });
    }

    public function damage(array $data, int $userId): array
    {
        return DB::transaction(function () use ($data, $userId) {
            $batch = Batch::lockForUpdate()->findOrFail($data['batch_id']);

            if ($batch->quantity < $data['quantity']) {
                throw new \Exception('الكمية المُتلفة تتجاوز كمية الدفعة');
            }

            $batch->decrement('quantity', $data['quantity']);

            $product = Product::lockForUpdate()->findOrFail($batch->product_id);
            $product->decrement('total_quantity', $data['quantity']);

            $movement = StockMovement::create([
                'user_id' => $userId,
                'department_id' => null,
                'product_id' => $batch->product_id,
                'batch_id' => $batch->id,
                'request_id' => null,
                'type' => 'damage',
                'quantity' => $data['quantity'],
                'notes' => $data['notes'] ?? null,
            ]);

            return [
                'batch' => new BatchResource($batch->fresh()),
                'product' => new ProductResource($product->fresh()),
                'movement' => $movement,
            ];
        });
    }
}
