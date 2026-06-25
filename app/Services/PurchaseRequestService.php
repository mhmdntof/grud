<?php

namespace App\Services;

use App\Models\PurchaseRequest;
use Illuminate\Support\Facades\DB;
use App\Models\User;

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
        'requester',
        'supplier',
        'items.product'
    ])
    ->where('manager_status', 'approved')
    ->where('committee_status', 'pending')
    ->where('request_type', 'urgent')
    ->latest()
    ->get();
}


//الطلبات العادية 


public function getPendingCommitteeNormalRequests()
{
    return PurchaseRequest::with([
        'requester',
        'supplier',
        'items.product'
    ])
    ->where('manager_status', 'approved')
    ->where('committee_status', 'pending')
    ->where('request_type', 'normal')
    ->latest()
    ->get();
}


//طلبات مدير المشفى للشراء المستعجلة 

public function getPendingManagerUrgentRequests()
{
    return PurchaseRequest::with([
        'requester',
        'supplier',
        'items.product'
    ])
    ->where('manager_status', 'pending')
    ->where('request_type', 'urgent')
    ->latest()
    ->get();
}


//الطلبات العادية 


public function getPendingManagerNormalRequests()
{
    return PurchaseRequest::with([
        'requester',
        'supplier',
        'items.product'
    ])
    ->where('manager_status', 'pending')
    ->where('request_type', 'normal')
    ->latest()
    ->get();
}



//عرض تفاصيل طلب شراء


public function getById($id)
{
    return PurchaseRequest::with([
        'requester',
        'supplier',
        'items.product'
    ])->findOrFail($id);
}


}