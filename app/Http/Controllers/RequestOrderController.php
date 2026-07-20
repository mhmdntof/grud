<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Requests\Requests\CreateRequestOrderRequest;
use App\Services\Requests\RequestOrderService;
use Illuminate\Support\Facades\Auth;
use App\Http\Requests\Requests\ManagerApprovalRequest;
use App\Http\Requests\Requests\WarehouseApprovalRequest;
use App\Http\Requests\RejectRequestOrderRequest;
use App\Http\Requests\ApproveRequestOrderByWarehouseRequest;


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

public function approveByManager($id)
{
    return response()->json([
        'message' => 'Request approved successfully.',
        'data' => $this->requestOrderService->approveByManager($id)
    ]);
}

//رفض المدير لطلب القسم 
public function rejectByManager(
    RejectRequestOrderRequest $request,
    $id
) {
    return response()->json([
        'message' => 'Request rejected successfully.',
        'data' => $this->requestOrderService->rejectByManager(
            $id,
            $request->validated()['rejection_reason']
        )
    ]);
}


//موافقة المستودع على طلب القسم 

public function approveByWarehouse(
    ApproveRequestOrderByWarehouseRequest $request,
    int $id
) {
    $result = $this->requestOrderService
        ->approveByWarehouse(
            $id,
            $request->validated()['items']
        );

    return response()->json([
        'message' => 'Request approved and ready for delivery.',
        'data' => $result
    ]);
}

//رفض المستودع لطلب القسم 

public function rejectByWarehouse(
    RejectRequestOrderRequest $request,
    int $id
)
{
    $result = $this->requestOrderService
        ->rejectByWarehouse(
            $id,
            $request->validated()['rejection_reason']
        );

    return response()->json([
        'message' => 'Request rejected successfully.',
        'data' => $result
    ]);
}


//طلبات رئيس المشفى العادية 


public function getPendingNormalRequests()
{
    return response()->json([
        'data' => $this->requestOrderService
            ->getPendingNormalRequests()
    ]);
}

    //طلبات المدير المستعجلة 



    public function getPendingUrgentRequests()
{
    return response()->json([
        'data' => $this->requestOrderService
            ->getPendingUrgentRequests()
    ]);
    
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

public function getRequestOrderById($id)
{
    $requestOrder = $this->requestOrderService
        ->getRequestOrderById($id);

    return response()->json([
        'data' => $requestOrder
    ]);
}


//استلام القسم للمواد 


public function confirmDelivery($id)
{
    $requestOrder = $this->requestOrderService
        ->confirmDelivery($id);

    return response()->json([
        'message' => 'Request delivered successfully.',
        'data' => $requestOrder,
    ]);
}


//رفض القسم للواد 


public function rejectDelivery(
    RejectRequestOrderRequest $request,
    int $id
)
{
    $result = $this->requestOrderService
        ->rejectDelivery(
            $id,
            $request->validated()['rejection_reason']
        );

    return response()->json([
        'message' => 'Delivery rejected successfully.',
        'data' => $result
    ]);
}



//طلبات قيد التنفيذ 

public function getInProgressRequests()
{
    return response()->json([
        'data' => $this->requestOrderService
            ->getInProgressRequests()
    ]);
}

//جلب جميع طلبات الاقسام 
public function getAllDepartmentRequests()
{
    return response()->json([
        'data' => $this->requestOrderService->getAllDepartmentRequests()
    ]);
}
//الطلبات المرفوضة 

public function getRejectedRequestOrders()
{
    return response()->json(
        $this->requestOrderService->getRejectedRequestOrders()
    );
}

}
