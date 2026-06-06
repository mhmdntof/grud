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

public function managerApproval(
    int $requestId,
    array $data
)
{
    $requestOrder = RequestOrder::findOrFail(
        $requestId
    );

    if (
        $requestOrder->manager_status !== 'pending'
    ) {
        throw new \Exception(
            'Request already processed'
        );
    }

    $requestOrder->manager_status =
        $data['status'];

    if (
        $data['status'] === 'rejected'
    ) {

        $requestOrder->rejection_reason =
            $data['rejection_reason'];
    }

    $requestOrder->save();

    return $requestOrder;
}

//موافقة المستودع على طلب القسم 

public function warehouseApproval(
    int $requestId,
    array $data
)
{
    return DB::transaction(function () use ($requestId, $data) {

        $requestOrder = RequestOrder::with('items')
            ->findOrFail($requestId);

        if ($requestOrder->manager_status !== 'approved') {

            throw new \Exception(
                'Hospital manager approval required first'
            );
        }

        if ($requestOrder->warehouse_status !== 'pending') {

            throw new \Exception(
                'Request already processed'
            );
        }

        // حالة الرفض
        if ($data['status'] === 'rejected') {

            $requestOrder->update([
                'warehouse_status' => 'rejected',
                'rejection_reason' => $data['rejection_reason']
            ]);

            return $requestOrder;
        }

        // التحقق من جميع المواد أولاً
        foreach ($requestOrder->items as $item) {

            $product = Product::findOrFail(
                $item->product_id
            );

            $remainingQuantity =
                $product->total_quantity -
                $item->quantity;

            if (
                $remainingQuantity <
                $product->minimum_stock
            ) {

                throw new \Exception(
                    "Cannot approve request. Product {$product->name} would fall below minimum stock."
                );
            }
        }

        // تنفيذ النقل
        foreach ($requestOrder->items as $item) {

            $product = Product::findOrFail(
                $item->product_id
            );

            // خصم من المستودع الرئيسي
            $product->decrement(
                'total_quantity',
                $item->quantity
            );

            // إضافة لمستودع القسم
            $departmentProduct =
                DepartmentProduct::firstOrNew([
                    'department_id' =>
                        $requestOrder->department_id,

                    'product_id' =>
                        $item->product_id
                ]);

            $departmentProduct->quantity =
                ($departmentProduct->quantity ?? 0)
                + $item->quantity;

            $departmentProduct->save();
        }

        $requestOrder->update([
            'warehouse_status' => 'approved'
        ]);

        return $requestOrder;
    });
}

//طلبات الادمن المستعجلة 
public function getPendingUrgentRequests()
{
    return RequestOrder::with([
        'department',
        'requester',
        'items.product'
    ])
    ->where('manager_status', 'pending')
    ->where('request_type', 'urgent')
    ->latest()
    ->get();
}

//طلبات الادمن العادية

public function getPendingNormalRequests()
{
    return RequestOrder::with([
        'department',
        'requester',
        'items.product'
    ])
    ->where('manager_status', 'pending')
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
    ->where('manager_status', 'approved')
    ->where('warehouse_status', 'pending')
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
    ->where('manager_status', 'approved')
    ->where('warehouse_status', 'pending')
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
}