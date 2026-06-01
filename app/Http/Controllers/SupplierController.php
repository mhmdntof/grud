<?php

namespace App\Http\Controllers;

use App\Http\Requests\Warehouse\CRUD_Suppliers\StoreSupplierRequest;
use App\Http\Requests\Warehouse\CRUD_Suppliers\UpdateSupplierRequest;
use App\Services\SupplierService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SupplierController extends Controller
{
    public function __construct(
        private SupplierService $supplierService
    ) {}

    public function index(Request $request): JsonResponse
    {
        $filters = $request->only(['search', 'is_active', 'sort_by', 'sort_order', 'per_page']);
        $result = $this->supplierService->getAll($filters);
        return $this->sendResponse($result, 'Suppliers retrieved successfully');
    }

    public function show(int $id): JsonResponse
    {
        $supplier = $this->supplierService->getById($id);
        return $this->sendResponse(
            new \App\Http\Resources\SupplierResource($supplier),
            'Supplier retrieved successfully'
        );
    }

    public function store(StoreSupplierRequest $request): JsonResponse
    {
        $supplier = $this->supplierService->create($request->validated());
        return $this->sendResponse(
            new \App\Http\Resources\SupplierResource($supplier),
            'Supplier created successfully',
            201
        );
    }

    public function update(UpdateSupplierRequest $request, int $id): JsonResponse
    {
        $supplier = $this->supplierService->update($id, $request->validated());
        return $this->sendResponse(
            new \App\Http\Resources\SupplierResource($supplier),
            'Supplier updated successfully'
        );
    }

    public function destroy(int $id): JsonResponse
    {
        $this->supplierService->delete($id);
        return $this->sendResponse(null, 'Supplier deleted successfully');
    }

    public function restore(int $id): JsonResponse
    {
        $supplier = $this->supplierService->restore($id);
        return $this->sendResponse(
            new \App\Http\Resources\SupplierResource($supplier),
            'Supplier restored successfully'
        );
    }
}
