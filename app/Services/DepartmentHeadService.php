<?php

namespace App\Services;

use App\Models\RequestOrder;
use App\Models\RequestOrderItem;
use App\Models\User;

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

            if ($order->manager_status !== 'pending') {
                throw new \Exception('لا يمكن إلغاء طلب تمت معالجته');
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
}
