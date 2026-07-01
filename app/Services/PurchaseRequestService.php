<?php

namespace App\Services;

use App\Models\PurchaseRequest;
use Illuminate\Support\Facades\DB;
use App\Models\User;
use App\Models\RequestOrder;

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
    return PurchaseRequest::with([
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
        'created_at'
    )
    ->where('status', 'in_progress')
    ->where('request_type', 'urgent')
    ->latest()
    ->get();
}

//الطلبات العادية 


public function getPendingCommitteeNormalRequests()
{

  return PurchaseRequest::with([
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
            'created_at'
        )
        ->where('status', 'in_progress')
        ->where('request_type', 'normal')
        ->latest()
        ->get();
}


//طلبات مدير المشفى للشراء المستعجلة 

public function getPendingManagerUrgentRequests()
{
    return PurchaseRequest::with([
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
            'created_at'
        )
        ->where('status', 'pending')
        ->where('request_type', 'urgent')
        ->latest()
        ->get();
}


//الطلبات العادية 


public function getPendingManagerNormalRequests()
{
    return PurchaseRequest::with([
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
            'created_at'
        )
        ->where('status', 'pending')
        ->where('request_type', 'normal')
        ->latest()
        ->get();
}



//عرض تفاصيل طلب شراء


public function getRequestOrderById($id)
{
    /** @var \App\Models\RequestOrder $request */
    $request = RequestOrder::with([
        'department',
        'requester',
        'items'
    ])->findOrFail($id);

    return [
        'id' => $request->id,
        'requested_by' => $request->requested_by,
        'request_type' => $request->request_type,
        'status' => $request->status,
        'expected_budget' => $request->expected_budget,
        'reason' => $request->reason,
        'created_at' => $request->created_at,

        'department_name' => $request->department->name ?? null,
        'requester_name' => $request->requester->name ?? null,

        'items' => $request->items->map(function ($item) {
            return [
                'product_id' => $item->product_id,
                'quantity' => $item->quantity,
                'unit' => $item->unit,
                'received_quantity' => $item->received_quantity,
            ];
        }),
    ];
}


}