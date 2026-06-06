<?php

namespace App\Http\Controllers;

use App\Http\Requests\Product\StoreProductRequest;
//use App\Http\Requests\StoreProductRequest;
use App\Http\Requests\AddBatchRequest;


use App\Services\ProductService;
use Illuminate\Http\JsonResponse;

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


}


