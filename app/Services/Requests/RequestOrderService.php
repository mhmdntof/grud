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
    'status' => 'pending',
]);
        $requestOrder->items()->createMany($data['items']);

        return $requestOrder->load([
            'items.product',
            'department',
            'requester'
        ]);
    });
}

//موافقة مدير المشفى على طلب القسم

public function approveByManager($id, int $userId)
{
    $requestOrder = RequestOrder::findOrFail($id);

    if ($requestOrder->status !== 'pending') {
        throw new \Exception('This request cannot be approved.');
    }

    $requestOrder->update([
        'status' => 'in_progress',
        'manager_status' => 'approved',
        'manager_approved_by' => $userId,
        'manager_approved_at' => now(),
        'rejection_reason' => null,
    ]);

    $requestOrder->load(['items.product', 'department', 'requester', 'managerApprover']);

    return $requestOrder;
}

//رفض المدير لطلب القسم
public function rejectByManager($id, string $reason, int $userId)
{
    $requestOrder = RequestOrder::findOrFail($id);

    if ($requestOrder->status !== 'pending') {
        throw new \Exception('This request cannot be rejected.');
    }

    $requestOrder->update([
        'status' => 'rejected',
        'manager_status' => 'rejected',
        'manager_approved_by' => $userId,
        'manager_approved_at' => now(),
        'manager_rejection_reason' => $reason,
        'rejection_reason' => $reason,
    ]);

    $requestOrder->load(['items.product', 'department', 'requester', 'managerApprover']);

    return $requestOrder;
}





//موافقة المستودع على طلب القسم

public function approveByWarehouse(int $requestOrderId, array $items, int $userId)
{
    $requestOrder = RequestOrder::with('items.product')
        ->findOrFail($requestOrderId);

    if ($requestOrder->status !== 'in_progress') {
        throw new \Exception('Only requests in progress can be approved.');
    }

    DB::transaction(function () use ($requestOrder, $items, $userId) {
        foreach ($items as $itemData) {
            $item = RequestOrderItem::where('request_order_id', $requestOrder->id)
                ->findOrFail($itemData['item_id']);

            $approvedQuantity = $itemData['approved_quantity'];

            if ($approvedQuantity > $item->quantity) {
                throw new \Exception('Approved quantity cannot exceed requested quantity.');
            }

            $product = Product::lockForUpdate()->findOrFail($item->product_id);

            if ($approvedQuantity > $product->total_quantity) {
                throw new \Exception("Available quantity for {$product->name} is {$product->total_quantity} only.");
            }

            $item->update(['approved_quantity' => $approvedQuantity]);
            $product->decrement('total_quantity', $approvedQuantity);
        }

        $requestOrder->update([
            'status' => 'ready_for_delivery',
            'warehouse_status' => 'approved',
            'warehouse_approved_by' => $userId,
            'warehouse_approved_at' => now(),
        ]);
    });

    $requestOrder->load(['items.product', 'department', 'requester', 'warehouseApprover']);

    return $requestOrder;
}

// رفض المستودع لطلب القسم


public function rejectByWarehouse(int $requestOrderId, string $reason, int $userId)
{
    $requestOrder = RequestOrder::findOrFail($requestOrderId);

    if ($requestOrder->status !== 'in_progress') {
        throw new \Exception('Only requests in progress can be rejected.');
    }

    $requestOrder->update([
        'status' => 'rejected',
        'warehouse_status' => 'rejected',
        'warehouse_approved_by' => $userId,
        'warehouse_approved_at' => now(),
        'warehouse_rejection_reason' => $reason,
        'rejection_reason' => $reason,
    ]);

    $requestOrder->load(['items.product', 'department', 'requester', 'warehouseApprover']);

    return $requestOrder;
}

//طلبات الادمن المستعجلة
public function getPendingUrgentRequests()
{
    return RequestOrder::with([
        'department',
        'user',
        'items.product'
    ])
    ->where('status', 'pending')
    ->where('request_type', 'urgent')
    ->latest()
    ->get();
}

//طلبات الادمن العادية

public function getPendingNormalRequests()
{
    return RequestOrder::with([
        'department',
        'user',
        'items.product'
    ])
    ->where('status', 'pending')
    ->where('request_type', 'normal')
    ->latest()
    ->get();
}

// طلبات رئيس المستودع العادية

public function getWarehousePendingNormalRequests()
{
    return RequestOrder::with([
        'department',
        'requester',
        'items.product'
    ])

    ->where('status', 'in_progress')
    ->where('request_type', 'normal')
    ->latest()
    ->get();
}



//طلبات رئيس المستودع المستعجلة

public function getWarehousePendingUrgentRequests()
{
    return RequestOrder::with([
        'department',
        'requester',
        'items.product'
    ])

    ->where('status', 'in_progress')
    ->where('request_type', 'urgent')
    ->latest()
    ->get();
}

// تفاصيل طلب المستودع

public function getRequestOrderById($id)
{
    return RequestOrder::with([
        'department',
        'requester',
        'items.product'
    ])->findOrFail($id);
}

// استلام القسم للمواد


public function confirmDelivery(int $requestOrderId)
{
    $requestOrder = RequestOrder::with('items')
        ->findOrFail($requestOrderId);

    if ($requestOrder->status !== 'ready_for_delivery') {
        throw new \Exception(
            'This request is not ready for delivery.'
        );
    }

    DB::transaction(function () use ($requestOrder) {

        foreach ($requestOrder->items as $item) {

            if (($item->approved_quantity ?? 0) <= 0) {
                continue;
            }

            $departmentProduct = DepartmentProduct::firstOrCreate(
                [
                    'department_id' => $requestOrder->department_id,
                    'product_id' => $item->product_id,
                ],
                [
                    'quantity' => 0,
                ]
            );

            $departmentProduct->increment(
                'quantity',
                $item->approved_quantity
            );
        }

        $requestOrder->update([
            'status' => 'delivered',
        ]);
    });

    return $requestOrder->fresh([
        'items.product',
    ]);
}



//رفض القسم للمواد


public function rejectDelivery(
    int $requestOrderId,
    string $reason
)
{
    $requestOrder = RequestOrder::with([
        'items.product'
    ])->findOrFail($requestOrderId);

    if ($requestOrder->status !== 'ready_for_delivery') {
        throw new \Exception(
            'Only requests ready for delivery can be rejected.'
        );
    }

    DB::transaction(function () use (
        $requestOrder,
        $reason
    ) {

        foreach ($requestOrder->items as $item) {

            $approvedQuantity =
                $item->approved_quantity ?? 0;

            if ($approvedQuantity > 0) {

                $item->product->increment(
                    'total_quantity',
                    $approvedQuantity
                );
            }
        }

        $requestOrder->update([
            'status' => 'delivery_rejected',
            'rejection_reason' => $reason,
        ]);
    });

    return $requestOrder->fresh([
        'items.product'
    ]);
}

// طلبات قيد التنفيذ

public function getInProgressRequests()
{
    return RequestOrder::with([
            'department',
            'user',
            'items' => function ($query) {
                $query->select(
                    'id',
                    'request_order_id',
                    'product_id',
                    'quantity',
                    
                    'approved_quantity'
                );
            }
        ])
        ->where('status', 'in_progress')
        ->latest()
        ->get();
}


//جلب جميع طلبات الاقسام


public function getAllDepartmentRequests()
{
    return RequestOrder::with([
        'department:id,name',
        'items:id,request_order_id,product_id,quantity',
        'items.product:id,name,type'
    ])
    ->orderBy('created_at', 'desc')
    ->get()
    ->map(function ($request) {
        return [
            'id' => $request->id,
            'department_name' => $request->department->name,
            'status' => $request->status,
            'request_type' => $request->request_type,
            'created_at' => $request->created_at,
            'items' => $request->items->map(function ($item) {
                return [
                    'product_id' => $item->product_id,
                    'product_name' => $item->product->name,
                    'quantity' => $item->quantity,
                ];
            }),
        ];
    });
}

// جلب طلبات رئيس قسم معين

public function getMyRequests(int $userId, array $filters = []): array
{
    $query = RequestOrder::where('requested_by', $userId)
        ->with(['items.product', 'department', 'requester']);

    // فلترة حسب الحالة
    if (!empty($filters['status'])) {
        $query->where('status', $filters['status']);
    }

    // فلترة حسب النوع
    if (!empty($filters['type'])) {
        $query->where('request_type', $filters['type']);
    }

    $query->orderBy('created_at', 'desc');

    $perPage = $filters['per_page'] ?? 15;
    $requests = $query->paginate($perPage);

    return [
        'requests' => \App\Http\Resources\RequestOrderResource::collection($requests),
        'pagination' => [
            'current_page' => $requests->currentPage(),
            'last_page' => $requests->lastPage(),
            'per_page' => $requests->perPage(),
            'total' => $requests->total(),
        ],
    ];
}

/**
 * إلغاء طلب
 */
public function cancelRequest(int $requestId, int $userId): array
{
    $request = RequestOrder::where('id', $requestId)
        ->where('requested_by', $userId)
        ->first();

    if (!$request) {
        throw new \Exception('الطلب غير موجود أو لا تملك صلاحية الوصول إليه');
    }

    if (!in_array($request->status, ['pending'])) {
        throw new \Exception('لا يمكن إلغاء طلب تمت معالجته');
    }

    $request->update(['status' => 'cancelled']);

    // إعادة تحميل Relations بعد التحديث
    $request->load(['items.product', 'department', 'requester']);

    return $request;
}
}
