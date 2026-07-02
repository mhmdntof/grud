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

            // ✔️ العامود الجديد
            'request_frequency' => $data['request_frequency'] ?? 'normal',
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

public function approveByManager($id)
{
    $requestOrder = RequestOrder::findOrFail($id);

    if ($requestOrder->status !== 'pending') {
        throw new \Exception('This request cannot be approved.');
    }

    $requestOrder->update([
        'status' => 'in_progress',
        'rejection_reason' => null,
    ]);

    return $requestOrder;
}

//رفض المدير لطلب القسم 
public function rejectByManager($id, string $reason)
{
    $requestOrder = RequestOrder::findOrFail($id);

    if ($requestOrder->status !== 'pending') {
        throw new \Exception('This request cannot be rejected.');
    }

    $requestOrder->update([
        'status' => 'rejected',
        'rejection_reason' => $reason,
    ]);

    return $requestOrder;
}





//موافقة المستودع على طلب القسم 

public function approveByWarehouse(
    int $requestOrderId,
    array $items
) {
    $requestOrder = RequestOrder::with('items.product')
        ->findOrFail($requestOrderId);

    if ($requestOrder->status !== 'in_progress') {
        throw new \Exception(
            'Only requests in progress can be approved.'
        );
    }

    DB::transaction(function () use ($requestOrder, $items) {

        foreach ($items as $itemData) {

            $item = RequestOrderItem::where(
                'request_order_id',
                $requestOrder->id
            )->findOrFail($itemData['item_id']);

            $approvedQuantity = $itemData['approved_quantity'];

            if ($approvedQuantity > $item->quantity) {
                throw new \Exception(
                    'Approved quantity cannot exceed requested quantity.'
                );
            }

            $product = Product::findOrFail($item->product_id);

            if ($approvedQuantity > $product->total_quantity) {
                throw new \Exception(
                    "Available quantity for {$product->name} is {$product->total_quantity} only."
                );
            }

            $item->update([
                'approved_quantity' => $approvedQuantity
            ]);

            // حجز الكمية مباشرة من المستودع
            $product->decrement(
                'total_quantity',
                $approvedQuantity
            );
        }

        $requestOrder->update([
            'status' => 'ready_for_delivery'
        ]);
    });

    return $requestOrder->fresh([
        'items.product'
    ]);
}


// رفض المستودع لطلب القسم 


public function rejectByWarehouse(
    int $requestOrderId,
    string $reason
)
{
    $requestOrder = RequestOrder::findOrFail(
        $requestOrderId
    );

    if ($requestOrder->status !== 'in_progress') {
        throw new \Exception(
            'Only requests in progress can be rejected.'
        );
    }

    $requestOrder->update([
        'status' => 'rejected',
        'rejection_reason' => $reason,
    ]);

    return $requestOrder;
}

//طلبات الادمن المستعجلة 
public function getPendingUrgentRequests()
{
    $requests = RequestOrder::with([
        'department',
        'user',
        'items.product.suppliers'
    ])
    ->where('status', 'pending')
    ->where('request_type', 'urgent')
    ->latest()
    ->get();

    return $requests->map(function ($request) {

        return [
            'id' => $request->id,
            'department_name' => $request->department->name ?? null,
            'user_name' => $request->user->name ?? null,
            'status' => $request->status,
            'request_type' => $request->request_type,
            'request_frequency' => $request->request_frequency,
            'created_at' => $request->created_at,

            'items' => $request->items->map(function ($item) {
                return [
                    'product_id' => $item->product_id,
                    'product_name' => $item->product->name ?? null,
                    'brand' => $item->product->brand ?? null,

                    // ✔ الموردين من product_supplier
                    'suppliers' => $item->product->suppliers->map(function ($supplier) {
                        return [
                            'id' => $supplier->id,
                            'name' => $supplier->name,
                        ];
                    }),

                    'quantity' => $item->quantity,
                ];
            })
        ];
    });
}

//طلبات الادمن العادية

public function getPendingNormalRequests()
{
    $requests = RequestOrder::with([
        'department',
        'user',
        'items.product.suppliers'
    ])
    ->where('status', 'pending')
    ->where('request_type', 'normal')
    ->latest()
    ->get();

    return $requests->map(function ($request) {

        return [
            'id' => $request->id,
            'department_name' => $request->department->name ?? null,
            'user_name' => $request->user->name ?? null,
            'status' => $request->status,
            'request_type' => $request->request_type,
            'request_frequency' => $request->request_frequency,
            'created_at' => $request->created_at,

            'items' => $request->items->map(function ($item) {
                return [
                    'product_id' => $item->product_id,
                    'product_name' => $item->product->name ?? null,
                    'brand' => $item->product->brand ?? null,

                    // ✔ الموردين من product_supplier
                    'suppliers' => $item->product->suppliers->map(function ($supplier) {
                        return [
                            'id' => $supplier->id,
                            'name' => $supplier->name,
                        ];
                    }),

                    'quantity' => $item->quantity,
                ];
            })
        ];
    });
}
// طلبات رئيس المستودع العادية 

public function getWarehousePendingNormalRequests()
{
    $requests = RequestOrder::with([
        'department',
        'requester',
        'items.product.suppliers'
    ])
    ->where('status', 'in_progress')
    ->where('request_type', 'normal')
    ->latest()
    ->get();

    return $requests->map(function ($request) {

        return [
            'id' => $request->id,
            'department_name' => $request->department->name ?? null,
            'requester_name' => $request->requester->name ?? null,
            'status' => $request->status,
            'request_type' => $request->request_type,
            'request_frequency' => $request->request_frequency, // ✔️ إضافة جديدة
            'created_at' => $request->created_at,

            'items' => $request->items->map(function ($item) {
                return [
                    'product_id' => $item->product_id,
                    'product_name' => $item->product->name ?? null,
                    'brand' => $item->product->brand ?? null,

                    // ✔️ الموردين من product_supplier
                    'suppliers' => $item->product->suppliers->map(function ($supplier) {
                        return [
                            'id' => $supplier->id,
                            'name' => $supplier->name,
                        ];
                    }),

                    'quantity' => $item->quantity,
                ];
            }),
        ];
    });
}


//طلبات رئيس المستودع المستعجلة 

public function getWarehousePendingUrgentRequests()
{
    $requests = RequestOrder::with([
        'department',
        'requester',
        'items.product.suppliers'
    ])
    ->where('status', 'in_progress')
    ->where('request_type', 'urgent')
    ->latest()
    ->get();

    return $requests->map(function ($request) {

        return [
            'id' => $request->id,
            'department_name' => $request->department->name ?? null,
            'requester_name' => $request->requester->name ?? null,
            'status' => $request->status,
            'request_type' => $request->request_type,
            'request_frequency' => $request->request_frequency, // ✔️ إضافة جديدة
            'created_at' => $request->created_at,

            'items' => $request->items->map(function ($item) {
                return [
                    'product_id' => $item->product_id,
                    'product_name' => $item->product->name ?? null,
                    'brand' => $item->product->brand ?? null,

                    // ✔️ الموردين من product_supplier
                    'suppliers' => $item->product->suppliers->map(function ($supplier) {
                        return [
                            'id' => $supplier->id,
                            'name' => $supplier->name,
                        ];
                    }),

                    'quantity' => $item->quantity,
                ];
            }),
        ];
    });
}

// تفاصيل طلب المستودع 

public function getRequestOrderById($id)
{
    /** @var \App\Models\RequestOrder $request */
    $request = RequestOrder::with([
        'department',
        'requester',
        'items.product.suppliers'
    ])->findOrFail($id);

    return [
        'id' => $request->id,
        'department_name' => $request->department->name ?? null,
        'requester_name' => $request->requester->name ?? null,
        'status' => $request->status,
        'request_type' => $request->request_type,
        'request_frequency' => $request->request_frequency, // ✔️ إضافة جديدة
        'created_at' => $request->created_at,

        'items' => $request->items->map(function ($item) {
            return [
                'product_id' => $item->product_id,
                'product_name' => $item->product->name ?? null,
                'brand' => $item->product->brand ?? null,

                // ✔️ suppliers من product_supplier
                'suppliers' => $item->product->suppliers->map(function ($supplier) {
                    return [
                        'id' => $supplier->id,
                        'name' => $supplier->name,
                    ];
                }),

                'quantity' => $item->quantity,
                'unit' => $item->unit,
                'received_quantity' => $item->received_quantity,
            ];
        }),
    ];
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
    $requests = RequestOrder::with([
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

    return $requests->map(function ($request) {

        return [
            'id' => $request->id,
            'department_name' => $request->department->name ?? null,
            'user_name' => $request->user->name ?? null,
            'status' => $request->status,
            'request_type' => $request->request_type,
            'created_at' => $request->created_at,

            'items' => $request->items->map(function ($item) {
                return [
                    'product_id' => $item->product_id,
                    'quantity' => $item->quantity,
                    'approved_quantity' => $item->approved_quantity,
                ];
            }),
        ];
    });
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

}