<?php

namespace App\Http\Controllers;

use App\Http\Requests\Product\StoreProductRequest;
use App\Http\Requests\AddBatchRequest;
use App\Http\Requests\SupplierRequest;
use App\Services\ProductService;
use Illuminate\Http\JsonResponse;
use App\Models\PurchaseRequest;
use App\Services\PurchaseRequestService;
use Illuminate\Http\Request;
use App\Http\Requests\AttachSupplierToProductRequest;

class ProductController extends Controller
{
    protected ProductService $productService;
    private $purchaseRequestService;

    public function __construct(ProductService $productService, PurchaseRequestService $purchaseRequestService)
    {
        $this->productService = $productService;
        $this->purchaseRequestService = $purchaseRequestService;
    }

    public function store(StoreProductRequest $request): JsonResponse
    {
        $product = $this->productService->create($request->validated());

        return response()->json([
            'message' => 'Product created successfully',
            'product' => $product
        ], 201);
    }

    public function receive(Request $request)
    {
        $request->validate([
            'purchase_request_id' => 'required|exists:purchase_requests,id',
            'items' => 'required|array|min:1',
            'items.*.product_id' => 'required|exists:products,id',
            'items.*.batch_number' => 'required|string',
            'items.*.quantity' => 'required|integer|min:1',
            'items.*.expire_date' => 'required|date',
            'items.*.purchase_price' => 'nullable|numeric'
        ]);

        $result = $this->productService->receivePurchaseRequest($request->all());

        if (isset($result['error'])) {
            return response()->json($result, 400);
        }

        return response()->json([
            'message' => 'Goods received successfully',
            'data' => $result
        ]);
    }

    // جلب مواد المستودع الرئيسي
    public function getWarehouseProducts(string $type)
    {
        return response()->json([
            'data' => $this->productService->getWarehouseProducts($type)
        ]);
    }

    // كل مواد المستودع
    public function getAllWarehouseProducts()
    {
        return response()->json([
            'data' => $this->productService->getAllWarehouseProducts()
        ]);
    }

    // ✅ من كود محمد (مفيد)
    public function getAllWarehouseProductsWith()
    {
        return response()->json([
            'data' => $this->productService->getAllWarehouseProductsWith()
        ]);
    }

    // ✅ من كود محمد (مفيد)
    public function addSupplirs(SupplierRequest $request)
    {
        $supplier = $this->productService->createSupplier($request->validated());

        return response()->json([
            'data' => $supplier,
            'message' => 'Supplier created successfully'
        ]);
    }

    // ✅ من كود محمد (مفيد)
    public function attachSupplier(AttachSupplierToProductRequest $request)
    {
        $result = $this->productService->attachSupplier($request->validated());

        if (isset($result['error'])) {
            return response()->json($result, 400);
        }

        return response()->json([
            'message' => 'Supplier attached successfully.',
            'data' => $result,
        ], 201);
    }

    // ✅ من كود محمد (مفيد)
    public function getAllSuppliersWithProducts()
    {
        $suppliers = $this->productService->getAllSuppliersWithProducts();

        return response()->json([
            'data' => $suppliers
        ]);
    }
}
