<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Requests\Requests\CreateRequestOrderRequest;
use App\Services\Requests\RequestOrderService;
use App\Http\Requests\Requests\ManagerApprovalRequest;
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
        auth()->user()
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
}
