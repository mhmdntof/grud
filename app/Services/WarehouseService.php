<?php

namespace App\Services;

use App\Http\Resources\BatchResource;
use App\Http\Resources\ProductResource;
use App\Http\Resources\RequestResource;
use App\Http\Resources\StockMovementResource;


use App\Models\Batch;
use App\Models\Product;
use App\Models\Request;
use App\Models\StockMovement;
use App\Models\RequestOrder;
use App\Models\RequestOrderItem;

use Illuminate\Support\Facades\DB;
class WarehouseService
{
    public function stockIn(array $data, int $userId): array
    {
        return DB::transaction(function () use ($data, $userId) {
            // ✅ أضفنا lockForUpdate()
            $product = Product::lockForUpdate()->find($data['product_id']);

            $batch = Batch::create([
                'product_id' => $data['product_id'],
                'supplier_id' => $data['supplier_id'],
                'batch_number' => $data['batch_number'],
                'quantity' => $data['quantity'],
                'expire_date' => $data['expire_date']?? null,
                'purchase_price' => $data['purchase_price'] ?? null,
                'notes' => $data['notes'] ?? null,
            ]);

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

        $requestModel = null;
        if (!empty($data['request_id'])) {
            $requestModel = Request::lockForUpdate()->findOrFail($data['request_id']);

            if ($requestModel->product_id != $data['product_id']) {
                throw new \Exception('المنتج لا يتطابق مع الطلب');
            }

            if ($requestModel->status !== 'ready') {
                throw new \Exception('الطلب يجب أن يكون بstatus "جاهز" (ready) أولاً');
            }

            // ✅ التحقق: لا تسليم جزئي (بناءً على ردك السابق)
            if ($data['quantity'] != $requestModel->approved_quantity) {
                throw new \Exception(
                    'يجب تسليم الكمية المعتمدة بالكامل (' . $requestModel->approved_quantity . ')'
                );
            }
        }

        // FIFO
        $batches = Batch::where('product_id', $data['product_id'])
            ->where('quantity', '>', 0)
            ->orderBy('expire_date', 'asc')
            ->lockForUpdate()
            ->get();

        $remaining = $data['quantity'];
        $movements = [];

        foreach ($batches as $batch) {
            if ($remaining <= 0) break;
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

        // ✅ الجديد: تحديث مخزون القسم
        if (!empty($data['department_id'])) {
            $deptProduct = \App\Models\DepartmentProduct::firstOrNew([
                'department_id' => $data['department_id'],
                'product_id' => $data['product_id'],
            ]);
            $currentQty = $deptProduct->quantity ?? 0;
            $deptProduct->quantity = $currentQty + $data['quantity'];
            $deptProduct->save();
        }

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

        public function getProducts(array $filters = []): array
    {
        $query = Product::query()
            ->with([
                'batches' => function ($q) {
                    $q->where('quantity', '>', 0)
                      ->orderBy('expire_date', 'asc');
                },
                'suppliers'
            ]);

        // البحث بالاسم أو الكود
        if (!empty($filters['search'])) {
            $search = $filters['search'];
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('code', 'like', "%{$search}%");
            });
        }

        // التصفية حسب النوع
        if (!empty($filters['type'])) {
            $query->where('type', $filters['type']);
        }

        // التصفية حسب الحالة
        if (!empty($filters['alert'])) {
            switch ($filters['alert']) {
                case 'low_stock':
                    $query->whereColumn('total_quantity', '<=', 'minimum_stock')
                          ->where('minimum_stock', '>', 0);
                    break;
                case 'out_of_stock':
                    $query->where('total_quantity', 0);
                    break;
            }
        }

        // الترتيب
        $sortBy = $filters['sort_by'] ?? 'created_at';
        $sortOrder = $filters['sort_order'] ?? 'desc';
        $allowedSorts = ['name', 'total_quantity', 'created_at'];

        if (in_array($sortBy, $allowedSorts)) {
            $query->orderBy($sortBy, $sortOrder);
        }

        // التصفح
        $perPage = $filters['per_page'] ?? 15;

        $products = $query->paginate($perPage);

        return [
            'products' => ProductResource::collection($products),
            'pagination' => [
                'current_page' => $products->currentPage(),
                'last_page' => $products->lastPage(),
                'per_page' => $products->perPage(),
                'total' => $products->total(),
            ],
        ];
    }

    public function getAlerts(): array
    {
        $lowStock = Product::whereColumn('total_quantity', '<=', 'minimum_stock')
            ->where('minimum_stock', '>', 0)
            ->get();

        $expiringSoon = Batch::whereNotNull('expire_date')
            ->where('expire_date', '<=', now()->addDays(30))
            ->where('expire_date', '>=', now())
            ->where('quantity', '>', 0)
            ->with('product')
            ->get();

        $expired = Batch::whereNotNull('expire_date')
            ->where('expire_date', '<', now())
            ->where('quantity', '>', 0)
            ->with('product')
            ->get();

        return [
            'low_stock' => ProductResource::collection($lowStock),
            'expiring_soon' => BatchResource::collection($expiringSoon),
            'expired' => BatchResource::collection($expired),
        ];
    }

    //عرض طلبات الاقسام
    public function getRequestOrders(array $filters = []): array
    {
        $query = RequestOrder::where('manager_status', 'approved')
            ->whereIn('warehouse_status', ['pending', 'approved', 'in_progress', 'ready'])
            ->with([
                'items.product:id,name,code,unit,total_quantity,minimum_stock',
                'department:id,name',
                'requester:id,name'
            ]);

        if (!empty($filters['department_id'])) {
            $query->where('department_id', $filters['department_id']);
        }

        if (!empty($filters['type'])) {
            $query->where('request_type', $filters['type']);
        }

        $query->orderByRaw("FIELD(warehouse_status, 'pending', 'approved', 'in_progress', 'ready')")
              ->orderBy('created_at', 'desc');

        $perPage = $filters['per_page'] ?? 15;
        $orders = $query->paginate($perPage);

        return [
            'orders' => $orders,
            'pagination' => [
                'current_page' => $orders->currentPage(),
                'last_page' => $orders->lastPage(),
                'per_page' => $orders->perPage(),
                'total' => $orders->total()
            ]
        ];
    }

    public function approveRequestOrder(int $orderId, array $approvedItems, int $userId): array
{
    return DB::transaction(function () use ($orderId, $approvedItems, $userId) {
        $order = RequestOrder::lockForUpdate()->findOrFail($orderId);

        if ($order->manager_status !== 'approved') {
            throw new \Exception('الطلب يجب أن يكون معتمداً من مدير المستشفى أولاً');
        }
        if ($order->warehouse_status !== 'pending') {
            throw new \Exception('الطلب يجب أن يكون معلقاً للموافقة عليه');
        }

        $approvedItemsData = [];
        $rejectedItemsData = [];

        foreach ($order->items as $item) {
            $product = Product::lockForUpdate()->find($item->product_id);
            $approvedQty = $approvedItems[$item->id] ?? 0;

            if ($approvedQty > 0) {
                // ✅ التحقق من الكمية المتاحة
                $availableQty = $product->total_quantity - ($product->reserved_quantity ?? 0);

                if ($availableQty < $approvedQty) {
                    throw new \Exception(
                        "الكمية المتاحة للمنتج {$product->name} هي {$availableQty} فقط. " .
                        "لا يمكن الموافقة على {$approvedQty}."
                    );
                }

                // ✅ حجز الكمية
                $product->increment('reserved_quantity', $approvedQty);

                $item->update([
                    'approved_quantity' => $approvedQty,
                    'reserved_quantity' => $approvedQty,
                    'rejection_reason' => null
                ]);

                $approvedItemsData[] = [
                    'product_id' => $item->product_id,
                    'product_name' => $product->name,
                    'requested_quantity' => $item->requested_quantity,
                    'approved_quantity' => $approvedQty,
                    'reserved_quantity' => $approvedQty,
                    'available_quantity' => $availableQty - $approvedQty,
                ];
            } else {
                $item->update([
                    'approved_quantity' => 0,
                    'reserved_quantity' => 0,
                    'rejection_reason' => 'تم رفض هذه المادة'
                ]);

                $rejectedItemsData[] = [
                    'product_id' => $item->product_id,
                    'product_name' => $product->name,
                    'requested_quantity' => $item->requested_quantity,
                    'reason' => 'تم رفض هذه المادة'
                ];
            }
        }

        if (empty($approvedItemsData)) {
            $order->update([
                'warehouse_status' => 'rejected',
                'rejection_reason' => 'جميع المواد مرفوضة'
            ]);
            throw new \Exception('تم رفض جميع المواد في الطلب');
        }

        $order->update(['warehouse_status' => 'approved']);

        return [
            'order' => $order->load([
                'items.product:id,name,code,unit',
                'department:id,name',
                'requester:id,name'
            ]),
            'approved_items' => $approvedItemsData,
            'rejected_items' => $rejectedItemsData,
            'message' => empty($rejectedItemsData)
                ? 'تمت الموافقة على جميع المواد وحجزها'
                : 'تمت الموافقة جزئياً — بعض المواد مرفوضة'
        ];
    });
}

    public function rejectRequestOrder(int $orderId, string $reason, int $userId): array
{
    return DB::transaction(function () use ($orderId, $reason, $userId) {
        $order = RequestOrder::lockForUpdate()->findOrFail($orderId);

        // ✅ التحقق من أن الطلب معتمد من مدير المستشفى
        if ($order->manager_status !== 'approved') {
            throw new \Exception('الطلب يجب أن يكون معتمداً من مدير المستشفى أولاً');
        }

        // ✅ السماح بالرفض في حالات: pending, approved, in_progress, ready
        $allowedStatuses = ['pending', 'approved', 'in_progress', 'ready'];
        if (!in_array($order->warehouse_status, $allowedStatuses)) {
            throw new \Exception(
                'لا يمكن رفض الطلب في حالته الحالية (' . $order->warehouse_status . ')'
            );
        }

        // ✅ إذا كان الطلب في حالة delivered أو rejected أو cancelled، لا يمكن الرفض
        if (in_array($order->warehouse_status, ['delivered', 'rejected', 'cancelled'])) {
            throw new \Exception('لا يمكن رفض طلب تم تسليمه أو رفضه أو إلغاؤه');
        }

        // ✅ تحرير الحجز من جميع المواد
        foreach ($order->items as $item) {
            if (($item->reserved_quantity ?? 0) > 0) {
                $product = Product::lockForUpdate()->find($item->product_id);
                $product->decrement('reserved_quantity', $item->reserved_quantity);

                $item->update([
                    'approved_quantity' => 0,
                    'reserved_quantity' => 0,
                    'rejection_reason' => $reason
                ]);
            }
        }

        $order->update([
            'warehouse_status' => 'rejected',
            'rejection_reason' => $reason
        ]);

        return [
            'order' => $order->load([
                'items.product:id,name,code,unit',
                'department:id,name',
                'requester:id,name'
            ]),
            'rejection_reason' => $reason,
            'message' => 'تم رفض الطلب وتحرير الحجز'
        ];
    });
}

    /**
     * تحضير الطلب (in_progress)
     */
    public function prepareRequestOrder(int $orderId, int $userId): array
    {
        return DB::transaction(function () use ($orderId, $userId) {
            $order = RequestOrder::lockForUpdate()->findOrFail($orderId);

            if ($order->warehouse_status !== 'approved') {
                throw new \Exception('الطلب يجب أن يكون معتمداً من المستودع أولاً');
            }

            $order->update(['warehouse_status' => 'in_progress']);

            return [
                'order' => $order->load([
                    'items.product:id,name,code,unit',
                    'department:id,name',
                    'requester:id,name'
                ]),
                'message' => 'الطلب قيد التنفيذ'
            ];
        });
    }

    /**
     * إعلان جاهزية الطلب (ready)
     */
    public function readyRequestOrder(int $orderId, int $userId): array
    {
        return DB::transaction(function () use ($orderId, $userId) {
            $order = RequestOrder::lockForUpdate()->findOrFail($orderId);

            if ($order->warehouse_status !== 'in_progress') {
                throw new \Exception('الطلب يجب أن يكون قيد التنفيذ أولاً');
            }

            $order->update(['warehouse_status' => 'ready']);

            return [
                'order' => $order->load([
                    'items.product:id,name,code,unit',
                    'department:id,name',
                    'requester:id,name'
                ]),
                'message' => 'الطلب جاهز للتسليم'
            ];
        });
    }

    /**
     * تسليم الطلب (delivered) - مع خصم من المخزون
     */
    public function deliverRequestOrder(int $orderId, int $userId): array
{
    return DB::transaction(function () use ($orderId, $userId) {
        $order = RequestOrder::lockForUpdate()->findOrFail($orderId);

        if ($order->warehouse_status !== 'ready') {
            throw new \Exception('الطلب يجب أن يكون جاهزاً أولاً');
        }

        $deliveredItems = [];
        $failedItems = [];

        foreach ($order->items as $item) {
            if (($item->approved_quantity ?? 0) <= 0) continue;

            try {
                $product = Product::lockForUpdate()->find($item->product_id);

                // ✅ خصم من total_quantity
                $product->decrement('total_quantity', $item->approved_quantity);

                // ✅ تحرير الحجز
                $product->decrement('reserved_quantity', $item->reserved_quantity ?? $item->approved_quantity);

                // ✅ إنشاء StockMovement
                StockMovement::create([
                    'user_id' => $userId,
                    'product_id' => $item->product_id,
                    'department_id' => $order->department_id,
                    'request_order_id' => $order->id,
                    'type' => 'out',
                    'quantity' => $item->approved_quantity,
                    'notes' => "تسليم طلب رقم {$order->id} - {$order->department->name}"
                ]);

                $item->update([
                    'delivered_quantity' => $item->approved_quantity,
                    'reserved_quantity' => 0
                ]);

                $deliveredItems[] = [
                    'product_id' => $item->product_id,
                    'product_name' => $item->product->name,
                    'approved_quantity' => $item->approved_quantity,
                    'delivered_quantity' => $item->approved_quantity
                ];
            } catch (\Exception $e) {
                $failedItems[] = [
                    'product_id' => $item->product_id,
                    'product_name' => $item->product->name,
                    'error' => $e->getMessage()
                ];
            }
        }

        if (!empty($failedItems)) {
            throw new \Exception('فشل تسليم بعض المواد: ' .
                collect($failedItems)->map(fn($item) => "{$item['product_name']}: {$item['error']}")->implode(', ')
            );
        }

        $order->update(['warehouse_status' => 'delivered']);

        return [
            'order' => $order->load([
                'items.product:id,name,code,unit',
                'department:id,name',
                'requester:id,name'
            ]),
            'delivered_items' => $deliveredItems,
            'message' => 'تم تسليم الطلب بنجاح'
        ];
    });
}

    public function getMovements(array $filters = []): array
    {
        $query = StockMovement::query()
            ->with([
                'user:id,name',
                'department:id,name',
                'product:id,name,code,unit',
                'batch:id,batch_number,expire_date',
                'request:id,status'
            ]);

        // تصفية حسب المنتج
        if (!empty($filters['product_id'])) {
            $query->where('product_id', $filters['product_id']);
        }

        // تصفية حسب القسم
        if (!empty($filters['department_id'])) {
            $query->where('department_id', $filters['department_id']);
        }

        // تصفية حسب النوع
        if (!empty($filters['type'])) {
            $query->where('type', $filters['type']);
        }

        // تصفية حسب المستخدم
        if (!empty($filters['user_id'])) {
            $query->where('user_id', $filters['user_id']);
        }

        // تصفية حسب التاريخ
        if (!empty($filters['from_date'])) {
            $query->whereDate('created_at', '>=', $filters['from_date']);
        }
        if (!empty($filters['to_date'])) {
            $query->whereDate('created_at', '<=', $filters['to_date']);
        }

        // الترتيب: الأحدث أولاً
        $query->orderBy('created_at', 'desc');

        $perPage = $filters['per_page'] ?? 15;
        $movements = $query->paginate($perPage);

        return [
            'movements' => StockMovementResource::collection($movements),
            'pagination' => [
                'current_page' => $movements->currentPage(),
                'last_page' => $movements->lastPage(),
                'per_page' => $movements->perPage(),
                'total' => $movements->total(),
            ],
        ];
    }
}
