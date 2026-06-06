<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Requests\Requests\CreateRequestOrderRequest;
use App\Services\Requests\RequestOrderService;
use Illuminate\Support\Facades\Auth;
use App\Http\Requests\Requests\ManagerApprovalRequest;
use App\Http\Requests\Requests\WarehouseApprovalRequest;


class RequestOrderController extends Controller
{
    protected $requestOrderService;

public function __construct(
    RequestOrderService $requestOrderService
)
{
    $this->requestOrderService = $requestOrderService;
}

public function store(
    CreateRequestOrderRequest $request
)
{
    $order = $this->requestOrderService->create(
        $request->validated(),
          Auth::user()
    );

    return response()->json([
        'message' => 'Request created successfully',
        'data' => $order
    ]);
}



//موافقة مدير المشفى على طلب القسم 

public function managerApproval(
    ManagerApprovalRequest $request,
    $id
)
{
    $order = $this->requestOrderService
        ->managerApproval(
            $id,
            $request->validated()
        );

    return response()->json([
        'message' => 'Request updated',
        'data' => $order
    ]);
}

//موافقة المستودع على طلب القسم 

public function warehouseApproval(
    WarehouseApprovalRequest $request,
    $id
)
{
    $order = $this->requestOrderService
        ->warehouseApproval(
            $id,
            $request->validated()
        );

    return response()->json([
        'message' => 'Request updated successfully',
        'data' => $order
    ]);
}

//طلبات رئيس المشفى المستعجلة 

public function pendingUrgent()
{
    return response()->json(
        $this->requestOrderService
            ->getPendingUrgentRequests()
    );
}

//طلبات رئيس المشفى العادية 


public function pendingNormal()
{
    return response()->json(
        $this->requestOrderService
            ->getPendingNormalRequests()
    );
}

// طلبات رئيس المستودع العادية 

public function warehousePendingNormal()
{
    return response()->json(
        $this->requestOrderService
            ->getWarehousePendingNormalRequests()
    );
}


//طلبات رئيس المستودع المستعجلة 

public function warehousePendingUrgent()
{
    return response()->json(
        $this->requestOrderService
            ->getWarehousePendingUrgentRequests()
    );
}
//تفاصيل طلب المستودع 

public function show($id)
{
    $requestOrder = $this->requestOrderService
        ->getRequestOrderById($id);

    return response()->json([
        'data' => $requestOrder
    ]);
}


}
