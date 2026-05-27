<?php

namespace App\Http\Controllers;

use App\Http\Requests\StockInRequest;
use App\Http\Requests\StockOutRequest;
use App\Http\Requests\DamageRequest;

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

}
