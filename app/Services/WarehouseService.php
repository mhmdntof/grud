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

    //عرض طلبات الاقسام
    public function getRequests(array $filters = []): array
    {
        $query = \App\Models\Request::query()
            ->with([
                'user:id,name',
                'department:id,name',
                'product:id,name,code,unit'
            ]);

        // تصفية حسب الحالة
        if (!empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        // تصفية حسب القسم
        if (!empty($filters['department_id'])) {
            $query->where('department_id', $filters['department_id']);
        }

        // تصفية حسب النوع
        if (!empty($filters['type'])) {
            $query->where('type', $filters['type']);
        }

        // تصفية حسب المنتج
        if (!empty($filters['product_id'])) {
            $query->where('product_id', $filters['product_id']);
        }

        // الترتيب: الطلبات المعلقة أولاً، ثم حسب التاريخ
        $query->orderByRaw("FIELD(status, 'pending', 'approved', 'in_progress', 'ready', 'delivered', 'rejected', 'cancelled')")
              ->orderBy('created_at', 'desc');

        // التصفح
        $perPage = $filters['per_page'] ?? 15;

        $requests = $query->paginate($perPage);

        return [
            'requests' => RequestResource::collection($requests),
            'pagination' => [
                'current_page' => $requests->currentPage(),
                'last_page' => $requests->lastPage(),
                'per_page' => $requests->perPage(),
                'total' => $requests->total(),
            ],
        ];
    }

    public function approveRequest(int $requestId, int $userId): array
    {
        return DB::transaction(function () use ($requestId, $userId) {
            $request = Request::lockForUpdate()->findOrFail($requestId);

            // التحقق من أن الطلب معلق
            if ($request->status !== 'pending') {
                throw new \Exception('الطلب يجب أن يكون معلقاً للموافقة عليه');
            }

            // التحقق من توفر المخزون (القبول كامل أو لا شيء)
            $product = Product::lockForUpdate()->findOrFail($request->product_id);

            if ($product->total_quantity < $request->requested_quantity) {
                throw new \Exception(
                    'المخزون غير كافٍ. المتوفر: ' . $product->total_quantity .
                    ' والمطلوب: ' . $request->requested_quantity
                );
            }

            // الموافقة على الطلب
            $request->update([
                'status' => 'approved',
                'approved_quantity' => $request->requested_quantity,
            ]);

            return [
                'request' => [
                    'id' => $request->id,
                    'status' => $request->status,
                    'approved_quantity' => $request->approved_quantity,
                    'product' => [
                        'id' => $product->id,
                        'name' => $product->name,
                        'available_quantity' => $product->total_quantity,
                    ],
                    'department' => [
                        'id' => $request->department->id,
                        'name' => $request->department->name,
                    ],
                ],
            ];
        });
    }

    public function rejectRequest(int $requestId, int $userId, string $reason): array
    {
        return DB::transaction(function () use ($requestId, $userId, $reason) {
            // ✅ أقفل الصف: أي عملية أخرى تنتظر
            $request = Request::lockForUpdate()->findOrFail($requestId);

            // التحقق من أنه لا يزال معلقاً (بعد الانتظار)
            if ($request->status !== 'pending') {
                throw new \Exception('الطلب يجب أن يكون معلقاً للرفض');
            }

            $request->update([
                'status' => 'rejected',
                'rejection_reason' => $reason,
            ]);

            return [
                'request' => [
                    'id' => $request->id,
                    'status' => $request->status,
                    'rejection_reason' => $request->rejection_reason,
                    'product' => [
                        'id' => $request->product->id,
                        'name' => $request->product->name,
                    ],
                    'department' => [
                        'id' => $request->department->id,
                        'name' => $request->department->name,
                    ],
                ],
            ];
        });
    }

    public function prepareRequest(int $requestId, int $userId): array
    {
        return DB::transaction(function () use ($requestId, $userId) {
            $request = Request::lockForUpdate()->findOrFail($requestId);

            if ($request->status !== 'approved') {
                throw new \Exception('الطلب يجب أن يكون معتمداً أولاً');
            }

            $request->update(['status' => 'in_progress']);

            return [
                'request' => [
                    'id' => $request->id,
                    'status' => $request->status,
                    'product' => [
                        'id' => $request->product->id,
                        'name' => $request->product->name,
                    ],
                    'department' => [
                        'id' => $request->department->id,
                        'name' => $request->department->name,
                    ],
                ],
            ];
        });
    }

    public function readyRequest(int $requestId, int $userId): array
    {
        return DB::transaction(function () use ($requestId, $userId) {
        // ✅ قفل الصف: أي عملية أخرى تنتظر حتى أُنهي
            $request = Request::lockForUpdate()->findOrFail($requestId);

            // ✅ تحقق مُجدّد بعد الانتظار
            if ($request->status !== 'in_progress') {
                throw new \Exception('الطلب يجب أن يكون قيد التنفيذ أولاً');
            }

            $request->update(['status' => 'ready']);

            return [
                'request' => [
                    'id' => $request->id,
                    'status' => $request->status,
                    'product' => [
                        'id' => $request->product->id,
                        'name' => $request->product->name,
                    ],
                    'department' => [
                        'id' => $request->department->id,
                        'name' => $request->department->name,
                    ],
                ],
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
