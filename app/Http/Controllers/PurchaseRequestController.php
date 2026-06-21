<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Http\Requests\StorePurchaseRequestRequest;
use App\Services\PurchaseRequestService;
class PurchaseRequestController extends Controller
{
    protected PurchaseRequestService $purchaseRequestService;

    public function __construct(
        PurchaseRequestService $purchaseRequestService
    ) {
        $this->purchaseRequestService = $purchaseRequestService;
    }

    public function store(StorePurchaseRequestRequest $request)
    {
       return $this->purchaseRequestService->create(
    $request->validated(),
    Auth::user()
);
    }



    //تابع موافقة المدير

    public function approveManager($id)
{
    $request = $this->purchaseRequestService->approveByManager($id);

    return response()->json([
        'message' => 'Request approved by manager',
        'data' => $request
    ]);
}

//تابع رفض المدير

public function rejectManager(Request $request, $id)
{
    $request->validate([
        'reason' => 'nullable|string'
    ]);

    $result = $this->purchaseRequestService->rejectByManager(
        $id,
       $request->user()->id,
        $request->reason
    );

    return response()->json([
        'message' => 'Request rejected by manager',
        'data' => $result
    ]);
}


//موافقة رئيس لجنة الشراء 

public function approveCommittee($id)
{
   
{
    $result = $this->purchaseRequestService->approveByCommittee($id);

    if (isset($result['error'])) {
        return response()->json($result, 400);
    }

    return response()->json([
        'message' => 'Request approved by committee',
        'data' => $result
    ]);
}
}


//تابع الرفض


public function rejectCommittee(Request $request, $id)
{
  
{
    $request->validate([
        'reason' => 'nullable|string'
    ]);

    $result = $this->purchaseRequestService->rejectByCommittee(
        $id,
        $request->user()->id,
        $request->reason
    );

    if (isset($result['error'])) {
        return response()->json($result, 400);
    }

    return response()->json([
        'message' => 'Request rejected successfully',
        'data' => $result
    ]);
}
}


//طلبات رئيس الشراء المستعجلة 

public function pendingCommitteeUrgent()
{
    return response()->json(
        $this->purchaseRequestService
            ->getPendingCommitteeUrgentRequests()
    );
}


//الطلبات العادية 

public function pendingCommitteeNormal()
{
    return response()->json(
        $this->purchaseRequestService
            ->getPendingCommitteeNormalRequests()
    );
}


//طلبات المدير للشراء المستعجلة 


public function pendingManagerUrgent()
{
    return response()->json(
        $this->purchaseRequestService
            ->getPendingManagerUrgentRequests()
    );
}


//الطلبات العادية 


public function pendingManagerNormal()
{
    return response()->json(
        $this->purchaseRequestService
            ->getPendingManagerNormalRequests()
    );
}


//عرض تفاصيل  طلب شراء}


public function show($id)
{
    $purchaseRequest = $this->purchaseRequestService
        ->getById($id);

    return response()->json([
        'data' => $purchaseRequest
    ]);
}



}