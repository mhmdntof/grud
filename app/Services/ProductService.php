<?php

namespace App\Services;

use App\Models\Product;
use App\Models\Batch;
use App\Models\Department;
use App\Models\DepartmentProduct;
use App\Models\Supplier;
use App\Models\ProductSupplier;


class ProductService
{
   public function create(array $data): Product
{
    return Product::create([
        'name' => $data['name'],

        'code' => $data['code'],

        'type' => $data['type'],

        'brand' => $data['brand'] ?? null,

        'minimum_stock' => $data['minimum_stock'] ?? 0,

        'maximum_stock' => $data['maximum_stock'] ?? null,

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


//اضافة موردين 

public function createSupplier(array $data)
{
    return Supplier::create([
        'name' => $data['name'],

        'email' => $data['email'] ?? null,

        'phone' => $data['phone'] ?? null,

        'address' => $data['address'] ?? null,

        'notes' => $data['notes'] ?? null,

        'is_active' => $data['is_active'] ?? true,
    ]);
}
// ربط المورد بالمنتج 

public function attachSupplier(array $data)
{
    $exists = ProductSupplier::where('product_id', $data['product_id'])
        ->where('supplier_id', $data['supplier_id'])
        ->exists();

    if ($exists) {
        return [
            'error' => 'Supplier is already attached to this product.'
        ];
    }

    return ProductSupplier::create([
        'product_id' => $data['product_id'],
        'supplier_id' => $data['supplier_id'],
        'notes' => $data['notes'] ?? null,
        'is_primary' => $data['is_primary'] ?? false,
    ]);
}

//جلب المورد مع المواد 

public function getAllSuppliersWithProducts()
{
    $suppliers = Supplier::select(
            'id',
            'name',
            'email',
            'phone',
            'address',
            'notes',
            'is_active'
        )
        ->with([
            'products:id,name,code,type'
        ])
        ->orderBy('name')
        ->get();

    $suppliers->each(function ($supplier) {
        $supplier->products->each(function ($product) {
            unset($product->pivot);
        });
    });

    return $suppliers;
}

}



