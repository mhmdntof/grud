<?php

namespace App\Services;

use App\Models\PurchaseRequest;
use Illuminate\Support\Facades\DB;
use App\Models\User;
use App\Models\RequestOrder;
use App\Http\Requests\UploadInvoiceRequest;
use Illuminate\Http\UploadedFile;
use CloudinaryLabs\CloudinaryLaravel\Facades\Cloudinary;


use Illuminate\Support\Facades\Storage;

class PurchaseRequestService
{
   public function create(array $data, User $user)
{
    return DB::transaction(function () use ($data, $user) {

        $purchaseRequest = PurchaseRequest::create([
            'requested_by' => $user->id,
            'supplier_id' => $data['supplier_id'] ?? null,
            'request_type' => $data['request_type'],
            'expected_budget' => $data['expected_budget'],
            'reason' => $data['reason'],
            'status' => 'pending',
            'request_frequency'=>$data['request_frequency'],
        ]);

        foreach ($data['items'] as $item) {

            $purchaseRequest->items()->create([
                'product_id' => $item['product_id'],
                'quantity' => $item['quantity'],
                'unit' => $item['unit'],
            ]);
        }

        return $purchaseRequest->load([
            'items.product',
            'supplier',
            'requester',
        ]);
    });
}
    //تابع موافقة المدير


   public function approveByManager($id)
{
    $request = PurchaseRequest::findOrFail($id);

    if ($request->status !== 'pending') {
        throw new \Exception('Request cannot be approved.');
    }

    $request->update([
        'status' => 'in_progress'
    ]);

    return $request;
}

// تابع رفض المدير 

public function rejectByManager($id, $rejectedBy, $reason = null)
{
    $request = PurchaseRequest::findOrFail($id);

    if ($request->status !== 'pending') {
        return [
            'error' => 'Request cannot be rejected.'
        ];
    }

    $request->update([
        'status' => 'rejected',
        'rejected_by' => $rejectedBy,
        'rejection_reason' => $reason,
    ]);

    return $request;
}
//تابع موافقة رئيس لجنة الشراء
public function approveByCommittee($id)
{
    $request = PurchaseRequest::findOrFail($id);

    if ($request->status !== 'in_progress') {
        return [
            'error' => 'Manager must approve first'
        ];
    }

    if ($request->status === 'awaiting_delivery' || $request->status === 'completed') {
        return [
            'error' => 'Already processed by committee'
        ];
    }

    $request->update([
        'status' => 'awaiting_delivery'
    ]);

    return $request;
}

//تابع الرفض

public function rejectByCommittee($id, $rejectedBy, $reason = null)
{
    $request = PurchaseRequest::findOrFail($id);

    if ($request->status !== 'in_progress') {
        return [
            'error' => 'Manager must approve first'
        ];
    }

    if ($request->status === 'rejected' || $request->status === 'completed') {
        return [
            'error' => 'Already processed'
        ];
    }

    $request->update([
        'status' => 'rejected',
        'rejected_by' => $rejectedBy,
        'rejection_reason' => $reason
    ]);

    return $request;
}

//طلبات رئيس الشراء المستعجلة 

public function getPendingCommitteeUrgentRequests()
{
    $requests = PurchaseRequest::with([
        'items.product.suppliers',
        'items.product' => function ($query) {
            $query->select('id', 'brand');
        },
        'items' => function ($query) {
            $query->select(
                'id',
                'purchase_request_id',
                'product_id',
                'quantity',
                'unit',
                'received_quantity'
            );
        }
    ])
    ->select(
        'id',
        'requested_by',
        'request_type',
        'status',
        'expected_budget',
        'reason',
        'created_at',
        'request_frequency'
    )
    ->where('status', 'in_progress')
    ->where('request_type', 'urgent')
    ->latest()
    ->get();

    return $requests->map(function ($request) {

        return [
            'id' => $request->id,
            'requested_by' => $request->requested_by,
            'request_type' => $request->request_type,
            'status' => $request->status,
            'expected_budget' => $request->expected_budget,
            'reason' => $request->reason,
            'created_at' => $request->created_at,
            'request_frequency' => $request->request_frequency,

            'items' => $request->items->map(function ($item) {

                return [
                    'product_id' => $item->product_id,
                    'product_name' => $item->product->name ?? null,
                    'brand' => $item->product->brand ?? null,

                    // ✔️ نخليها نظيفة بدون pivot
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
    });
}

//الطلبات العادية 


public function getPendingCommitteeNormalRequests()
{

  $requests = PurchaseRequest::with([
        'items.product.suppliers',
        'items.product' => function ($query) {
            $query->select('id', 'brand');
        },
        'items' => function ($query) {
            $query->select(
                'id',
                'purchase_request_id',
                'product_id',
                'quantity',
                'unit',
                'received_quantity'
            );
        }
    ])
    ->select(
        'id',
        'requested_by',
        'request_type',
        'status',
        'expected_budget',
        'reason',
        'created_at',
        'request_frequency'
    )
    ->where('status', 'in_progress')
    ->where('request_type', 'normal')
    ->latest()
    ->get();

    return $requests->map(function ($request) {

        return [
            'id' => $request->id,
            'requested_by' => $request->requested_by,
            'request_type' => $request->request_type,
            'status' => $request->status,
            'expected_budget' => $request->expected_budget,
            'reason' => $request->reason,
            'created_at' => $request->created_at,
            'request_frequency' => $request->request_frequency,

            'items' => $request->items->map(function ($item) {

                return [
                    'product_id' => $item->product_id,
                    'product_name' => $item->product->name ?? null,
                    'brand' => $item->product->brand ?? null,

                    // ✔️ نخليها نظيفة بدون pivot
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
    });
}


//طلبات مدير المشفى للشراء المستعجلة 

public function getPendingManagerUrgentRequests()
{
    $requests = PurchaseRequest::with([
        'items.product.suppliers',
        'items.product' => function ($query) {
            $query->select('id', 'brand');
        },
        'items' => function ($query) {
            $query->select(
                'id',
                'purchase_request_id',
                'product_id',
                'quantity',
                'unit',
                'received_quantity'
            );
        }
    ])
    ->select(
        'id',
        'requested_by',
        'request_type',
        'status',
        'expected_budget',
        'reason',
        'created_at',
        'request_frequency'
    )
    ->where('status', 'pending')
    ->where('request_type', 'urgent')
    ->latest()
    ->get();

    return $requests->map(function ($request) {

        return [
            'id' => $request->id,
            'requested_by' => $request->requested_by,
            'request_type' => $request->request_type,
            'status' => $request->status,
            'expected_budget' => $request->expected_budget,
            'reason' => $request->reason,
            'created_at' => $request->created_at,
            'request_frequency' => $request->request_frequency,

            'items' => $request->items->map(function ($item) {

                return [
                    'product_id' => $item->product_id,
                    'product_name' => $item->product->name ?? null,
                    'brand' => $item->product->brand ?? null,

                    // ✔️ نخليها نظيفة بدون pivot
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
    });
}

//الطلبات العادية 


public function getPendingManagerNormalRequests()
{
   $requests = PurchaseRequest::with([
        'items.product.suppliers',
        'items.product' => function ($query) {
            $query->select('id', 'brand');
        },
        'items' => function ($query) {
            $query->select(
                'id',
                'purchase_request_id',
                'product_id',
                'quantity',
                'unit',
                'received_quantity'
            );
        }
    ])
    ->select(
        'id',
        'requested_by',
        'request_type',
        'status',
        'expected_budget',
        'reason',
        'created_at',
        'request_frequency'
    )
    ->where('status', 'pending')
    ->where('request_type', 'normal')
    ->latest()
    ->get();

    return $requests->map(function ($request) {

        return [
            'id' => $request->id,
            'requested_by' => $request->requested_by,
            'request_type' => $request->request_type,
            'status' => $request->status,
            'expected_budget' => $request->expected_budget,
            'reason' => $request->reason,
            'created_at' => $request->created_at,
            'request_frequency' => $request->request_frequency,

            'items' => $request->items->map(function ($item) {

                return [
                    'product_id' => $item->product_id,
                    'product_name' => $item->product->name ?? null,
                    'brand' => $item->product->brand ?? null,

                    // ✔️ نخليها نظيفة بدون pivot
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
    });
}



//عرض تفاصيل طلب شراء


public function getById($id)
{
    /** @var \App\Models\RequestOrder $request */
    $request = RequestOrder::with([
        'department',
        'requester',
        'items.product.suppliers',
        'items' => function ($query) {
            $query->select(
                'id',
                'request_order_id',
                'product_id',
                'quantity'
            );
        }
    ])->findOrFail($id);

    return [
        'id' => $request->id,
        'requested_by' => $request->requested_by,
        'request_type' => $request->request_type,
        'request_frequency' => $request->request_frequency,
        'status' => $request->status,
        'expected_budget' => $request->expected_budget,
        'reason' => $request->reason,
        'created_at' => $request->created_at,

        'department_name' => $request->department->name ?? null,
        'requester_name' => $request->requester->name ?? null,

        'items' => $request->items->map(function ($item) {

            return [
                'product_id' => $item->product_id,
                'product_name' => $item->product->name ?? null,
                'brand' => $item->product->brand ?? null,

                'unit' => $item->product->unit ?? null,

                'suppliers' => $item->product->suppliers->map(function ($supplier) {
                    return [
                        'id' => $supplier->id,
                        'name' => $supplier->name,
                    ];
                })->values(),

                'quantity' => $item->quantity,
            ];
        })->values(),
    ];
}

//جميع طلبات الشراء للمدير 


public function getPendingManagerRequests()
{
    return PurchaseRequest::with([
        'items.product.suppliers',
        'items.product' => function ($query) {
            $query->select('id', 'brand', 'name');
        },
        'items' => function ($query) {
            $query->select(
                'id',
                'purchase_request_id',
                'product_id',
                'quantity',
                'unit',
                'received_quantity'
            );
        }
    ])
    ->select(
        'id',
        'requested_by',
        'request_type',
        'status',
        'expected_budget',
        'reason',
        'created_at',
        'request_frequency'
    )
    ->where('status', 'pending')
    ->latest()
    ->get()
    ->map(function ($request) {

        return [
            'id' => $request->id,
            'requested_by' => $request->requested_by,
            'request_type' => $request->request_type,
            'status' => $request->status,
            'expected_budget' => $request->expected_budget,
            'reason' => $request->reason,
            'created_at' => $request->created_at,
            'request_frequency' => $request->request_frequency,

            'items' => $request->items->map(function ($item) {

                return [
                    'product_id' => $item->product_id,
                    'product_name' => $item->product->name ?? null,
                    'brand' => $item->product->brand ?? null,

                    // ✔️ تنظيف الموردين (بدون pivot)
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
    });
}


//رفع فاتورة 


public function uploadInvoice(
    int $purchaseRequestId,
    UploadedFile $invoice,
    ?string $invoiceNumber = null
)
{
    $request = PurchaseRequest::findOrFail($purchaseRequestId);

    if ($request->status !== 'awaiting_delivery') {
        throw new \Exception(
            'Invoice can only be uploaded when request is awaiting delivery.'
        );
    }

    // رفع الصورة إلى Cloudinary
    $uploadedFile = Cloudinary::upload(
        $invoice->getRealPath(),
        [
            'folder' => 'purchase-invoices',
            'resource_type' => 'image',
        ]
    );

    $request->update([
        'invoice_file' => $uploadedFile->getSecurePath(), // رابط الصورة
        'invoice_number' => $invoiceNumber,
        'invoice_uploaded_at' => now(),
        'status' => 'invoice_uploaded',
    ]);

    return $request->fresh();
}


}