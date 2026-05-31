<?php

namespace App\Http\Controllers;

use App\Http\Requests\StockInRequest;
use App\Http\Requests\StockOutRequest;
use App\Http\Requests\DamageRequest;
use Illuminate\Http\Request;

use App\Http\Resources\BatchResource;
use App\Http\Resources\ProductResource;

use App\Models\Batch;
use App\Models\Product;
use App\Http\Requests\Warehouse\ApproveRequest;
use App\Http\Requests\Warehouse\RejectRequest;
use App\Http\Requests\Warehouse\PrepareRequest;
use App\Http\Requests\Warehouse\ReadyRequest;

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
        $lowStock = Product::whereColumn('total_quantity', '<=', 'minimum_stock')
            ->where('minimum_stock', '>', 0)
            ->get();

        $expiringSoon = Batch::where('expire_date', '<=', now()->addDays(30))
            ->where('quantity', '>', 0)
            ->with('product')
            ->get();

        return $this->sendResponse([
            'low_stock' => ProductResource::collection($lowStock),
            'expiring_soon' => BatchResource::collection($expiringSoon),
        ], 'Alerts retrieved successfully');
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
        public function requests(Request $request): JsonResponse
    {
        $filters = $request->only([
            'status',
            'department_id',
            'type',
            'product_id',
            'per_page'
        ]);

        $result = $this->warehouseService->getRequests($filters);

        return $this->sendResponse($result, 'Requests retrieved successfully');
    }

    public function approve(ApproveRequest $request): JsonResponse
    {
        $result = $this->warehouseService->approveRequest(
            $request->validated()['request_id'],
            $request->user()->id
        );

        return $this->sendResponse($result, 'Request approved successfully');
    }

    public function reject(RejectRequest $request): JsonResponse
    {
        $result = $this->warehouseService->rejectRequest(
            $request->validated()['request_id'],
            $request->user()->id,
            $request->validated()['rejection_reason']
        );

        return $this->sendResponse($result, 'Request rejected successfully');
    }

    public function prepare(PrepareRequest $request): JsonResponse
    {
        $result = $this->warehouseService->prepareRequest(
            $request->validated()['request_id'],
            $request->user()->id
        );

        return $this->sendResponse($result, 'Request moved to in_progress successfully');
    }

        public function ready(ReadyRequest $request): JsonResponse
    {
        $result = $this->warehouseService->readyRequest(
            $request->validated()['request_id'],
            $request->user()->id
        );

        return $this->sendResponse($result, 'Request moved to ready successfully');
    }
}
