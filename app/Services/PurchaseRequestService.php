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
                'requester'
            ]);
        });
    }


    //تابع موافقة المدير


    public function approveByManager($id)
{
    $request = PurchaseRequest::findOrFail($id);

    if ($request->manager_status !== 'pending') {
        throw new \Exception('Request already processed by manager');
    }

    $request->update([
        'manager_status' => 'approved'
    ]);

    return $request;
}


// تابع رفض المدير 

public function rejectByManager($id, $reason = null)
{
    $request = PurchaseRequest::findOrFail($id);

    if ($request->manager_status !== 'pending') {
        throw new \Exception('Request already processed by manager');
    }

    $request->update([
        'manager_status' => 'rejected',
        'rejection_reason' => $reason
    ]);

    return $request;
}
//تابع موافقة رئيس لجنة الشراء

public function approveByCommittee($id)
{
    $request = PurchaseRequest::findOrFail($id);

    if ($request->manager_status !== 'approved') {
        throw new \Exception('Manager must approve first');
    }

    if ($request->committee_status !== 'pending') {
        throw new \Exception('Already processed by committee');
    }

    $request->update([
        'committee_status' => 'approved'
    ]);

    return $request;
}

//تابع الرفض

public function rejectByCommittee($id, $reason = null)
{
    $request = PurchaseRequest::findOrFail($id);

    if ($request->manager_status !== 'approved') {
        throw new \Exception('Manager must approve first');
    }

    if ($request->committee_status !== 'pending') {
        throw new \Exception('Already processed by committee');
    }

    $request->update([
        'committee_status' => 'rejected',
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