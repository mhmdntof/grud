<?php

namespace App\Services;

use App\Models\RequestOrder;
use App\Models\RequestOrderItem;
use App\Models\User;
use App\Models\Product;
use App\Http\Resources\ProductResource;
use App\Models\DepartmentProduct;

use Illuminate\Support\Facades\DB;

class DepartmentHeadService
{
    public function submitRequest(array $data, int $userId): array
    {
        return DB::transaction(function () use ($data, $userId) {
            $user = \App\Models\User::findOrFail($userId);

            if (!$user->department_id) {
                throw new \Exception('المستخدم غير مرتبط بقسم');
            }

            $order = RequestOrder::create([
                'department_id' => $user->department_id,
                'requested_by' => $userId,
                'request_type' => $data['request_type'],
                'manager_status' => 'pending',
                'warehouse_status' => 'pending',
                'notes' => $data['notes'] ?? null,
                'recurring_frequency' => $data['recurring_frequency'] ?? null,
            ]);

            foreach ($data['items'] as $item) {
                RequestOrderItem::create([
                    'request_order_id' => $order->id,
                    'product_id' => $item['product_id'],
                    'requested_quantity' => $item['quantity'],
                ]);
            }

            return [
                'order' => $order->load([
                    'items.product:id,name,code,unit',
                    'department:id,name',
                    'requester:id,name'
                ])
            ];
        });
    }

    public function getMyRequests(int $userId, array $filters = []): array
    {
        $query = RequestOrder::where('requested_by', $userId)
            ->with([
                'items.product:id,name,code,unit',
                'department:id,name',
                'requester:id,name'
            ]);

        // فلترة حسب النوع
        if (!empty($filters['type'])) {
            $query->where('request_type', $filters['type']);
        }

        // فلترة حسب الحالة
        if (!empty($filters['status'])) {
            $status = $filters['status'];
            $query->where(function ($q) use ($status) {
                $q->where('manager_status', $status)
                  ->orWhere('warehouse_status', $status);
            });
        }

        $query->orderBy('created_at', 'desc');
        $perPage = $filters['per_page'] ?? 15;
        $orders = $query->paginate($perPage);

        return [
            'requests' => $orders,
            'pagination' => [
                'current_page' => $orders->currentPage(),
                'last_page' => $orders->lastPage(),
                'per_page' => $orders->perPage(),
                'total' => $orders->total()
            ]
        ];
    }

    /**
     * إلغاء طلب (فقط إذا كان pending)
     */
    public function cancelRequest(int $orderId, int $userId): array
{
    return DB::transaction(function () use ($orderId, $userId) {
        $order = RequestOrder::lockForUpdate()
            ->where('id', $orderId)
            ->where('requested_by', $userId)
            ->first();

        if (!$order) {
            throw new \Exception('الطلب غير موجود أو لا تملك صلاحية الوصول إليه');
        }

        if ($order->warehouse_status === 'delivered') {
            throw new \Exception('لا يمكن إلغاء طلب تم تسليمه');
        }

        // ✅ تحرير الحجز
        foreach ($order->items as $item) {
            if (($item->reserved_quantity ?? 0) > 0) {
                $product = Product::lockForUpdate()->find($item->product_id);
                $product->decrement('reserved_quantity', $item->reserved_quantity);
                $item->update(['reserved_quantity' => 0]);
            }
        }

        $order->update([
            'manager_status' => 'cancelled',
            'warehouse_status' => 'cancelled'
        ]);

        return [
            'order' => $order->load([
                'items.product:id,name,code,unit',
                'department:id,name',
                'requester:id,name'
            ])
        ];
    });
}

    public function updateRequest(int $orderId, array $data, int $userId): array
{
    return DB::transaction(function () use ($orderId, $data, $userId) {
        // 🔒 قفل الصف لمنع Race Conditions
        $order = RequestOrder::lockForUpdate()
            ->where('id', $orderId)
            ->where('requested_by', $userId)
            ->first();

        if (!$order) {
            throw new \Exception('الطلب غير موجود أو لا تملك صلاحية الوصول إليه');
        }

        // ✅ التحقق من حالة الطلب (لا يمكن التعديل بعد الموافقة)
        if ($order->manager_status !== 'pending') {
            throw new \Exception('لا يمكن تعديل طلب تمت معالجته من قبل مدير المستشفى');
        }

        // ✅ تحديث البيانات الأساسية
        $order->update([
            'request_type' => $data['request_type'] ?? $order->request_type,
            'notes' => $data['notes'] ?? $order->notes,
            'recurring_frequency' => $data['recurring_frequency'] ?? $order->recurring_frequency,
        ]);

        // ✅ تحديث المواد إذا تم إرسالها
        if (isset($data['items'])) {
            // التحقق من وجود المنتجات
            $productIds = collect($data['items'])->pluck('product_id');
            $existingProducts = Product::whereIn('id', $productIds)->count();

            if ($existingProducts !== $productIds->count()) {
                throw new \Exception('بعض المنتجات غير موجودة');
            }

            // حذف المواد القديمة
            $order->items()->delete();

            // إضافة المواد الجديدة
            foreach ($data['items'] as $item) {
                RequestOrderItem::create([
                    'request_order_id' => $order->id,
                    'product_id' => $item['product_id'],
                    'requested_quantity' => $item['quantity'],
                ]);
            }
        }

        return [
            'order' => $order->load([
                'items.product:id,name,code,unit',
                'department:id,name',
                'requester:id,name'
            ])
        ];
    });
}

public function getAvailableProducts(array $filters = []): array
{
    $query = Product::query()
        ->selectRaw('*, (total_quantity - COALESCE(reserved_quantity, 0)) as available_quantity')
        ->whereRaw('(total_quantity - COALESCE(reserved_quantity, 0)) > 0')
        ->select('id', 'name', 'code', 'type', 'unit', 'total_quantity', 'reserved_quantity', 'minimum_stock');

    if (!empty($filters['search'])) {
        $search = trim($filters['search']);
        $query->where(function ($q) use ($search) {
            $q->where('name', 'like', "%{$search}%")
              ->orWhere('code', 'like', "%{$search}%");
        });
    }

    if (!empty($filters['type'])) {
        $query->where('type', $filters['type']);
    }

    $query->orderBy('name', 'asc');
    $perPage = min($filters['per_page'] ?? 15, 100);
    $products = $query->paginate($perPage);

    return [
        'products' => ProductResource::collection($products),
        'pagination' => [
            'current_page' => $products->currentPage(),
            'last_page' => $products->lastPage(),
            'per_page' => $products->perPage(),
            'total' => $products->total(),
        ]
    ];
}

public function confirmReceipt(int $orderId, array $data, int $userId): array
{
    return DB::transaction(function () use ($orderId, $data, $userId) {
        // 🔒 قفل الصف
        $order = RequestOrder::lockForUpdate()
            ->where('id', $orderId)
            ->where('requested_by', $userId)
            ->first();

        if (!$order) {
            throw new \Exception('الطلب غير موجود');
        }

        // ✅ التحقق من حالة الطلب
        if ($order->warehouse_status !== 'delivered') {
            throw new \Exception('الطلب لم يتم تسليمه بعد من المستودع');
        }

        // ✅ معالجة كل مادة
        $receivedItems = [];
        foreach ($data['items'] as $itemData) {
            $item = RequestOrderItem::lockForUpdate()
                ->where('id', $itemData['id'])
                ->where('request_order_id', $order->id)
                ->first();

            if (!$item) {
                throw new \Exception('المادة غير موجودة في هذا الطلب');
            }

            // ✅ التحقق من أن الكمية المستلمة لا تتجاوز المسلمة
            if ($itemData['received_quantity'] > $item->delivered_quantity) {
                throw new \Exception(
                    "الكمية المستلمة للمنتج {$item->product->name} " .
                    "لا يمكن أن تتجاوز الكمية المسلمة ({$item->delivered_quantity})"
                );
            }

            // ✅ تحديث الكمية المستلمة
            $item->update([
                'received_quantity' => $itemData['received_quantity']
            ]);

            // ✅ إضافة إلى مخزون القسم
            if ($itemData['received_quantity'] > 0) {
                $departmentProduct = DepartmentProduct::firstOrCreate(
                    [
                        'department_id' => $order->department_id,
                        'product_id' => $item->product_id,
                    ],
                    ['quantity' => 0]
                );

                $departmentProduct->increment('quantity', $itemData['received_quantity']);
            }

            $receivedItems[] = [
                'product_id' => $item->product_id,
                'product_name' => $item->product->name,
                'delivered_quantity' => $item->delivered_quantity,
                'received_quantity' => $itemData['received_quantity'],
            ];
        }

        // ✅ تحديث حالة الطلب
        $order->update([
            'notes' => ($order->notes ? $order->notes . ' | ' : '') .
                      'تم تأكيد الاستلام من قبل القسم بتاريخ ' . now()->format('Y-m-d H:i:s') .
                      (isset($data['notes']) ? ' - ملاحظات: ' . $data['notes'] : '')
        ]);

        return [
            'order' => $order->load([
                'items.product:id,name,code,unit',
                'department:id,name',
                'requester:id,name'
            ]),
            'received_items' => $receivedItems,
            'message' => 'تم تأكيد استلام الطلب بنجاح'
        ];
    });
}

public function returnRequest(array $data, int $userId): array
{
    return DB::transaction(function () use ($data, $userId) {
        $user = User::findOrFail($userId);

        if (!$user->department_id) {
            throw new \Exception('المستخدم غير مرتبط بقسم');
        }

        // ✅ التحقق من وجود المنتجات
        $productIds = collect($data['items'])->pluck('product_id');
        $existingProducts = Product::whereIn('id', $productIds)->count();

        if ($existingProducts !== $productIds->count()) {
            throw new \Exception('بعض المنتجات غير موجودة');
        }

        // ✅ إنشاء طلب من نوع 'return'
        $order = RequestOrder::create([
            'department_id' => $user->department_id,
            'requested_by' => $userId,
            'request_type' => 'return',
            'manager_status' => 'pending',
            'warehouse_status' => 'pending',
            'notes' => 'طلب إعادة مواد للمستودع - السبب: ' . $data['reason'] .
                      (isset($data['notes']) ? ' | ملاحظات: ' . $data['notes'] : ''),
        ]);

        // ✅ إضافة المواد
        foreach ($data['items'] as $item) {
            RequestOrderItem::create([
                'request_order_id' => $order->id,
                'product_id' => $item['product_id'],
                'requested_quantity' => $item['quantity'],
            ]);
        }

        return [
            'order' => $order->load([
                'items.product:id,name,code,unit',
                'department:id,name',
                'requester:id,name'
            ]),
            'message' => 'تم إنشاء طلب الإعادة بنجاح'
        ];
    });
}
}
