<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreProductRequest;
use App\Services\ProductService;
use Illuminate\Http\JsonResponse;

class ProductController extends Controller
{
    protected ProductService $productService;

    // حقن كلاس السيرفيس عبر الـ Constructor
    public function __construct(ProductService $productService)
    {
        $this->productService = $productService;
    }

    /**
     * تخزين منتج جديد في المستودع الطبي
     */
    public function store(StoreProductRequest $request): JsonResponse
    {
        // جلب البيانات التي تم التحقق منها فقط وتمريرها للسيرفيس
        $product = $this->productService->create($request->validated());

        return response()->json([
            'message' => 'Product created successfully',
            'product' => $product
        ], 201);
    }
}