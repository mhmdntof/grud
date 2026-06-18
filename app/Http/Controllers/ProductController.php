<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreProductRequest;
use App\Http\Requests\AddBatchRequest;

use App\Http\Requests\SupplierRequest;
use App\Services\ProductService;
use Illuminate\Http\JsonResponse;
use App\Http\Requests\AttachSupplierToProductRequest;

class ProductController extends Controller
{
    protected ProductService $productService;

    public function __construct(ProductService $productService)
    {
        $this->productService = $productService;
    }

    public function store(StoreProductRequest $request): JsonResponse
    {
        $product = $this->productService
            ->create($request->validated());

        return response()->json([

            'message' => 'Product created successfully',

            'product' => $product

        ], 201);
        }


public function addBatch(AddBatchRequest $request)
{
    $batch = $this->productService
        ->addBatch($request->validated());

    return response()->json([
        'message' => 'Batch added successfully',
        'data' => $batch
    ]);
}

//جلب مواد المستودع الرئيسي

public function getWarehouseProducts(string $type)
{
    return response()->json([
        'data' => $this->productService
            ->getWarehouseProducts($type)
    ]);
}

//كل مواد المستودع  

public function getAllWarehouseProducts()
{
    return response()->json([
        'data' => $this->productService->getAllWarehouseProducts()
    ]);
}



//اضافة مورد 

public function addSupplirs(SupplierRequest $request)
{
    $supplier = $this->productService->createSupplier(
        $request->validated()
    );

    return response()->json([
        'data' => $supplier,
        'message' => 'Supplier created successfully'
    ]);
}
 //ربط المورد بالمنتج 


 public function attachSupplier(
    AttachSupplierToProductRequest $request
) {
    $result = $this->productService->attachSupplier(
        $request->validated()
    );

    if (isset($result['error'])) {
        return response()->json($result, 400);
    }

    return response()->json([
        'message' => 'Supplier attached successfully.',
        'data' => $result,
    ], 201);
}

//جلب المورد مع المواد 


public function getAllSuppliersWithProducts()
{
    $suppliers = $this->productService->getAllSuppliersWithProducts();

    return response()->json([
        'data' => $suppliers
    ]);
}

        }


