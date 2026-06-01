<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Requests\Warehouse\StockInRequest;
use App\Http\Requests\Warehouse\StockOutRequest;
use App\Http\Requests\Warehouse\DamageRequest;
use App\Http\Requests\Warehouse\ApproveRequest;
use App\Http\Requests\Warehouse\RejectRequest;
use App\Http\Requests\Warehouse\PrepareRequest;
use App\Http\Requests\Warehouse\ReadyRequest;


use App\Http\Resources\BatchResource;
use App\Http\Resources\ProductResource;

use App\Models\Batch;
use App\Models\Product;

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
        // 1️⃣ مخزون منخفض (كل المواد)
        $lowStock = Product::whereColumn('total_quantity', '<=', 'minimum_stock')
            ->where('minimum_stock', '>', 0)
            ->get();

        // 2️⃣ مواد تنتهي قريباً (فقط التي لها expire_date)
        $expiringSoon = Batch::whereNotNull('expire_date')
            ->where('expire_date', '<=', now()->addDays(30))
            ->where('expire_date', '>=', now())  // ✅ لم تنتهِ بعد
            ->where('quantity', '>', 0)
            ->with('product')
            ->get();

        // 3️⃣ مواد منتهية (فقط التي لها expire_date وانتهت)
        $expired = Batch::whereNotNull('expire_date')
            ->where('expire_date', '<', now())  // ✅ انتهت
            ->where('quantity', '>', 0)
            ->with('product')
            ->get();

        return $this->sendResponse([
            'low_stock' => ProductResource::collection($lowStock),
            'expiring_soon' => BatchResource::collection($expiringSoon),
            'expired' => BatchResource::collection($expired),  // ✅ جديد
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
            $request->validated('request_id'),
            $request->user()->id
        );

        return $this->sendResponse($result, 'Request approved successfully');
    }

    public function reject(RejectRequest $request): JsonResponse
    {
        $result = $this->warehouseService->rejectRequest(
            $request->validated('request_id'),
            $request->user()->id,
            $request->validated('rejection_reason')
        );

        return $this->sendResponse($result, 'Request rejected successfully');
    }

    public function prepare(PrepareRequest $request): JsonResponse
    {
        $result = $this->warehouseService->prepareRequest(
            $request->validated('request_id'),
            $request->user()->id
        );

        return $this->sendResponse($result, 'Request moved to in_progress successfully');
    }

        public function ready(ReadyRequest $request): JsonResponse
    {
        $result = $this->warehouseService->readyRequest(
            $request->validated('request_id'),
            $request->user()->id
        );

        return $this->sendResponse($result, 'Request moved to ready successfully');
    }
}
