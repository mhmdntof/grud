<?php

namespace App\Services;

use App\Models\Product;
use App\Models\Batch;
use App\Models\Department;
use App\Models\DepartmentProduct;


class ProductService
{
    public function create(array $data): Product
    {
        return Product::create([

            'name' => $data['name'],

            'code' => $data['code'],

            'type' => $data['type'],

            'minimum_stock' => $data['minimum_stock'] ?? 0,

            'unit' => $data['unit'] ?? null,

            'description' => $data['description'] ?? null,

            // تبدأ الكمية من الصفر
            'total_quantity' => 0,
        ]);
 
      }



      public function addBatch(array $data)
{
    $batch = Batch::create([
        'product_id' => $data['product_id'],
        'batch_number' => $data['batch_number'],
        'quantity' => $data['quantity'],
        'expire_date' => $data['expire_date'],
        'purchase_price' => $data['purchase_price'] ?? null,
    ]);

    $product = Product::findOrFail($data['product_id']);

    $product->total_quantity += $data['quantity'];

    $product->save();

    return $batch;
}



//جلب مواد المستودع الرئيسي 

public function getWarehouseProducts(string $type)
{
    return Product::with([
        'batches',
        'suppliers'
    ])
    ->where('type', $type)
    ->get();
}

//جلب مواد القسم 


public function getDepartmentProducts(
    string $departmentName,
    string $type
)
{
    $department = Department::where(
        'name',
        $departmentName
    )->firstOrFail();

    return DepartmentProduct::query()
        ->join(
            'products',
            'department_products.product_id',
            '=',
            'products.id'
        )
        ->where(
            'department_products.department_id',
            $department->id
        )
        ->where(
            'products.type',
            $type
        )
        ->select(
            'products.id as product_id',
            'products.name as product_name',
            'department_products.quantity'
        )
        ->orderBy('products.name')
        ->get();
}

//كل مواد المستودع 

public function getAllWarehouseProducts()
{
    return Product::select(
        'id',
        'name',
        'type',
        'total_quantity'
    )
    ->orderBy('name')
    ->get();
}

}



