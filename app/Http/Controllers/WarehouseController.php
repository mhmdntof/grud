<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Requests\Warehouse\StockInRequest;
use App\Http\Requests\Warehouse\StockOutRequest;
use App\Http\Requests\Warehouse\DamageRequest;

use App\Services\WarehouseService;
use Illuminate\Http\JsonResponse;

class WarehouseController extends Controller
{
    public function __construct(
        private WarehouseService $warehouseService
    ) {}

    public function stockIn(StockInRequest $request)
    {
        $result = $this->warehouseService->stockIn(
            $request->validated(),
            $request->user()->id
        );

        return $this->sendResponse($result, 'Stock in recorded successfully', 201);
    }

    public function stockOut(StockOutRequest $request): JsonResponse
    {
        $result = $this->warehouseService->stockOut(
            $request->validated(),
            $request->user()->id
        );

        return $this->sendResponse($result, 'Stock out recorded successfully');
    }

    public function damage(DamageRequest $request): JsonResponse
    {
        $result = $this->warehouseService->damage(
            $request->validated(),
            $request->user()->id
        );

        return $this->sendResponse($result, 'Damage recorded successfully');
    }

    public function alerts(): JsonResponse
    {
        return $this->sendResponse(
            $this->warehouseService->getAlerts(),
            'Alerts retrieved successfully'
        );
    }

    //عرض ال Products
        public function index(Request $request): JsonResponse
    {
        $filters = $request->only([
            'search',
            'type',
            'alert',
            'sort_by',
            'sort_order',
            'per_page'
        ]);

        $result = $this->warehouseService->getProducts($filters);

        return $this->sendResponse($result, 'Products retrieved successfully');
    }

    //عرض طلبات الاقسام
    public function requestOrders(Request $request): JsonResponse
{
    $result = $this->warehouseService->getRequestOrders(
        $request->only(['department_id', 'type', 'per_page'])
    );
    return $this->sendResponse($result, 'Request orders retrieved successfully');
}

public function approveRequestOrder(int $id, Request $request): JsonResponse
{
    $request->validate([
        'items' => 'required|array',
        'items.*.id' => 'required|exists:request_order_items,id',
        'items.*.approved_quantity' => 'required|integer|min:0',
    ]);

    // تحويل المصفوفة إلى [item_id => approved_quantity]
    $approvedItems = collect($request->items)
        ->pluck('approved_quantity', 'id')
        ->toArray();

    $result = $this->warehouseService->approveRequestOrder(
        $id,
        $approvedItems,
        $request->user()->id
    );
    return $this->sendResponse($result, 'Request order approved successfully');
}

public function rejectRequestOrder(Request $request, int $id): JsonResponse
{
    $request->validate([
        'rejection_reason' => 'required|string|max:1000'
    ]);

    $result = $this->warehouseService->rejectRequestOrder(
        $id,
        $request->input('rejection_reason'),
        $request->user()->id
    );
    return $this->sendResponse($result, 'Request order rejected successfully');
}

public function prepareRequestOrder(int $id, Request $request): JsonResponse
{
    $result = $this->warehouseService->prepareRequestOrder(
        $id,
        $request->user()->id
    );
    return $this->sendResponse($result, 'Request order moved to in_progress successfully');
}

public function readyRequestOrder(int $id, Request $request): JsonResponse
{
    $result = $this->warehouseService->readyRequestOrder(
        $id,
        $request->user()->id
    );
    return $this->sendResponse($result, 'Request order moved to ready successfully');
}

public function deliverRequestOrder(int $id, Request $request): JsonResponse
{
    $result = $this->warehouseService->deliverRequestOrder(
        $id,
        $request->user()->id
    );
    return $this->sendResponse($result, 'Request order delivered successfully');
}
}
