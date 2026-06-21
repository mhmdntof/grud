<?php

namespace App\Services\Requests;

use App\Models\RequestOrder;
use App\Models\User;
use App\Models\RequestOrderItem;
use App\Models\DepartmentProduct;
use App\Models\Product;
use Illuminate\Support\Facades\DB;

class RequestOrderService
{
    public function create(array $data, User $user)
    {
        return DB::transaction(function () use ($data, $user) {
            $requestOrder = RequestOrder::create([
                'department_id' => $user->department_id,
                'requested_by' => $user->id,
                'request_type' => $data['request_type'],
                'manager_status' => 'pending',
                'warehouse_status' => 'pending',
                'notes' => $data['notes'] ?? null,
                'recurring_frequency' => $data['recurring_frequency'] ?? null,
            ]);

            foreach ($data['items'] as $item) {
                RequestOrderItem::create([
                    'request_order_id' => $requestOrder->id,
                    'product_id' => $item['product_id'],
                    'requested_quantity' => $item['quantity'],
                ]);
            }

            return $requestOrder->load([
                'items.product',
                'department',
                'requester'
            ]);
        });
    }

    // موافقة مدير المستشفى على طلب القسم
    public function approveByManager($id)
    {
        $requestOrder = RequestOrder::findOrFail($id);

        if ($requestOrder->manager_status !== 'pending') {
            throw new \Exception('This request cannot be approved.');
        }

        $requestOrder->update([
            'manager_status' => 'approved',
            'rejection_reason' => null,
        ]);

        return $requestOrder;
    }

    // رفض المدير لطلب القسم
    public function rejectByManager($id, string $reason)
    {
        $requestOrder = RequestOrder::findOrFail($id);

        if ($requestOrder->manager_status !== 'pending') {
            throw new \Exception('This request cannot be rejected.');
        }

        $requestOrder->update([
            'manager_status' => 'rejected',
            'warehouse_status' => 'rejected',
            'rejection_reason' => $reason,
        ]);

        return $requestOrder;
    }

    // موافقة المستودع على طلب القسم
    public function approveByWarehouse(int $requestOrderId, array $items)
    {
        $requestOrder = RequestOrder::with('items.product')->findOrFail($requestOrderId);

        if ($requestOrder->manager_status !== 'approved') {
            throw new \Exception('Hospital manager approval required first');
        }

        if ($requestOrder->warehouse_status !== 'pending') {
            throw new \Exception('Request already processed');
        }

        DB::transaction(function () use ($requestOrder, $items) {
            foreach ($items as $itemData) {
                $item = RequestOrderItem::where('request_order_id', $requestOrder->id)
                    ->findOrFail($itemData['item_id']);

                $approvedQuantity = $itemData['approved_quantity'];

                if ($approvedQuantity > $item->requested_quantity) {
                    throw new \Exception('Approved quantity cannot exceed requested quantity.');
                }

                $product = Product::findOrFail($item->product_id);

                if ($approvedQuantity > $product->total_quantity) {
                    throw new \Exception("Available quantity for {$product->name} is {$product->total_quantity} only.");
                }

                $item->update(['approved_quantity' => $approvedQuantity]);

                // حجز الكمية مباشرة من المستودع
                $product->decrement('total_quantity', $approvedQuantity);
            }

            $requestOrder->update(['warehouse_status' => 'approved']);
        });

        return $requestOrder->fresh(['items.product']);
    }

    // رفض المستودع لطلب القسم
    public function rejectByWarehouse(int $requestOrderId, string $reason)
    {
        $requestOrder = RequestOrder::findOrFail($requestOrderId);

        if ($requestOrder->manager_status !== 'approved') {
            throw new \Exception('Hospital manager approval required first');
        }

        if ($requestOrder->warehouse_status !== 'pending') {
            throw new \Exception('Request already processed');
        }

        $requestOrder->update([
            'warehouse_status' => 'rejected',
            'rejection_reason' => $reason,
        ]);

        return $requestOrder;
    }

    // طلبات مدير المستشفى المستعجلة
    public function getPendingUrgentRequests()
    {
        return RequestOrder::with(['department', 'requester', 'items.product'])
            ->where('manager_status', 'pending')
            ->where('request_type', 'urgent')
            ->latest()
            ->get();
    }

    // طلبات مدير المستشفى العادية
    public function getPendingNormalRequests()
    {
        return RequestOrder::with(['department', 'requester', 'items.product'])
            ->where('manager_status', 'pending')
            ->where('request_type', 'normal')
            ->latest()
            ->get();
    }

    // طلبات مدير المستودع العادية
    public function getWarehousePendingNormalRequests()
    {
        return RequestOrder::with(['department', 'requester', 'items.product'])
            ->where('manager_status', 'approved')
            ->where('warehouse_status', 'pending')
            ->where('request_type', 'normal')
            ->latest()
            ->get();
    }

    // طلبات مدير المستودع المستعجلة
    public function getWarehousePendingUrgentRequests()
    {
        return RequestOrder::with(['department', 'requester', 'items.product'])
            ->where('manager_status', 'approved')
            ->where('warehouse_status', 'pending')
            ->where('request_type', 'urgent')
            ->latest()
            ->get();
    }

    // تفاصيل طلب
    public function getRequestOrderById($id)
    {
        return RequestOrder::with(['department', 'requester', 'items.product'])->findOrFail($id);
    }

    // استلام القسم للمواد
    public function confirmDelivery(int $requestOrderId)
    {
        $requestOrder = RequestOrder::with('items')->findOrFail($requestOrderId);

        if ($requestOrder->warehouse_status !== 'delivered') {
            throw new \Exception('This request is not ready for delivery.');
        }

        DB::transaction(function () use ($requestOrder) {
            foreach ($requestOrder->items as $item) {
                if (($item->approved_quantity ?? 0) <= 0) {
                    continue;
                }

                $departmentProduct = DepartmentProduct::firstOrCreate(
                    ['department_id' => $requestOrder->department_id, 'product_id' => $item->product_id],
                    ['quantity' => 0]
                );

                $departmentProduct->increment('quantity', $item->approved_quantity);
            }

            $requestOrder->update(['warehouse_status' => 'received']);
        });

        return $requestOrder->fresh(['items.product']);
    }

    // رفض القسم للمواد
    public function rejectDelivery(int $requestOrderId, string $reason)
    {
        $requestOrder = RequestOrder::with(['items.product'])->findOrFail($requestOrderId);

        if ($requestOrder->warehouse_status !== 'delivered') {
            throw new \Exception('Only requests delivered can be rejected.');
        }

        DB::transaction(function () use ($requestOrder, $reason) {
            foreach ($requestOrder->items as $item) {
                $approvedQuantity = $item->approved_quantity ?? 0;

                if ($approvedQuantity > 0) {
                    $item->product->increment('total_quantity', $approvedQuantity);
                }
            }

            $requestOrder->update([
                'warehouse_status' => 'delivery_rejected',
                'rejection_reason' => $reason,
            ]);
        });

        return $requestOrder->fresh(['items.product']);
    }

    // طلبات قيد التنفيذ
    public function getInProgressRequests()
    {
        return RequestOrder::with(['department', 'requester', 'items.product'])
            ->where('warehouse_status', 'in_progress')
            ->latest()
            ->get();
    }

    // جلب جميع طلبات الأقسام
    public function getAllDepartmentRequests()
    {
        return RequestOrder::with([
            'department:id,name',
            'items:id,request_order_id,product_id,requested_quantity',
            'items.product:id,name,type'
        ])
        ->orderBy('created_at', 'desc')
        ->get()
        ->map(function ($request) {
            return [
                'id' => $request->id,
                'department_name' => $request->department->name,
                'manager_status' => $request->manager_status,
                'warehouse_status' => $request->warehouse_status,
                'request_type' => $request->request_type,
                'created_at' => $request->created_at,
                'items' => $request->items->map(function ($item) {
                    return [
                        'product_id' => $item->product_id,
                        'product_name' => $item->product->name,
                        'requested_quantity' => $item->requested_quantity,
                    ];
                }),
            ];
        });
    }

    // طلبات رئيس القسم
    public function getMyRequests(int $userId, array $filters = []): array
    {
        $query = RequestOrder::where('requested_by', $userId)
            ->with(['items.product:id,name,code,unit', 'department:id,name']);

        if (!empty($filters['status'])) {
            $query->where('manager_status', $filters['status']);
        }

        if (!empty($filters['type'])) {
            $query->where('request_type', $filters['type']);
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
                'total' => $orders->total(),
            ],
        ];
    }

    // إلغاء طلب
    public function cancel(int $orderId, int $userId): array
    {
        return DB::transaction(function () use ($orderId, $userId) {
            $order = RequestOrder::lockForUpdate()
                ->where('id', $orderId)
                ->where('requested_by', $userId)
                ->first();

            if (!$order) {
                throw new \Exception('الطلب غير موجود أو لا تملك صلاحية الوصول إليه');
            }

            if ($order->manager_status !== 'pending') {
                throw new \Exception('لا يمكن إلغاء طلب تمت معالجته');
            }

            $order->update(['manager_status' => 'cancelled']);

            return ['order' => $order->load(['items.product', 'department'])];
        });
    }
}
