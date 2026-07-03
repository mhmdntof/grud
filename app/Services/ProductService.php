<?php

namespace App\Services;

use App\Models\Product;
use App\Models\Batch;
use App\Models\Department;
use App\Models\DepartmentProduct;
use App\Models\Supplier;
use App\Models\ProductSupplier;
use App\Models\PurchaseRequestItem;
use Illuminate\Support\Facades\DB;
use App\Models\PurchaseRequest;


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

        'storage_location'=>$data['storage_location']?? null,

        // تبدأ الكمية من الصفر
        'total_quantity' => 0,
    ]);
}



public function receivePurchaseRequest(array $data)
{
    return DB::transaction(function () use ($data) {

        // ✔️ جلب الطلب مع المواد
        $request = PurchaseRequest::with('items')->findOrFail($data['purchase_request_id']);

        // ✔️ التأكد من الحالة
        if ($request->status !== 'awaiting_delivery') {
            return [
                'error' => 'Request is not ready for receiving'
            ];
        }

        // ✔️ جلب المورد من الطلب (مو من الدخل)
        $supplierId = $request->supplier_id;

        foreach ($data['items'] as $item) {

            $product = Product::findOrFail($item['product_id']);

            // ✔️ إنشاء Batch
            Batch::create([
                'product_id' => $item['product_id'],
                'supplier_id' => $supplierId,
                'batch_number' => $item['batch_number'],
                'quantity' => $item['quantity'],
                'expire_date' => $item['expire_date'],
                'purchase_price' => $item['purchase_price'] ?? null,
                'notes' => $item['notes'] ?? null,
            ]);

            // ✔️ تحديث المخزون
            $product->increment('total_quantity', $item['quantity']);

            // ✔️ تحديث الكمية المستلمة
            PurchaseRequestItem::where('purchase_request_id', $request->id)
                ->where('product_id', $item['product_id'])
                ->increment('received_quantity', $item['quantity']);
        }

        // ✔️ تحديث حالة الطلب
        $request->update([
            'status' => 'delivered'
        ]);

        return [
            'message' => 'Purchase request received successfully'
        ];
    });
}


//جلب مواد المستودع الرئيسي 

public function getWarehouseProducts(string $type)
{
    return Product::with([
        'batches' => function ($query) {
            $query->select(
                'id',
                'product_id',
                'quantity',
                'expire_date',
                'created_at'
            );
        }
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
    $department = Department::where('name', $departmentName)->firstOrFail();

    $products = DepartmentProduct::with([
            'product.suppliers'
        ])
        ->where('department_id', $department->id)
        ->whereHas('product', function ($query) use ($type) {
            $query->where('type', $type);
        })
        ->get();

    return [
        'department_name' => $department->name,
        'products' => $products->map(function ($item) {

            return [
                'product_id' => $item->product_id,
                'product_name' => $item->product->name ?? null,
                'brand' => $item->product->brand ?? null,
                'quantity' => $item->quantity,

                // ✔ suppliers بشكل نظيف
                'suppliers' => $item->product->suppliers->map(function ($supplier) {
                    return [
                        'id' => $supplier->id,
                        'name' => $supplier->name,
                    ];
                })->values(),
            ];
        })->values(),
    ];
}

//كل مواد المستودع 

public function getAllWarehouseProducts()
{
    return Product::select(
        'id',
        'name',
        'type',
        'total_quantity',
        'unit'
    )
    ->orderBy('name')
    ->get();
}

//جلب مواد المستودع مع تاريخ اخر دفعة 

public function getAllWarehouseProductsWith()
{
    $products = Product::select(
            'id',
            'name',
            'type',
            'total_quantity',
            'minimum_stock',
            'maximum_stock',
            'unit'
        )
        ->withMax([
            'batches as last_batch_date'
        ], 'created_at')
        ->orderBy('name')
        ->get();

    $products->each(function ($product) {

        if ($product->total_quantity == 0) {
            $product->status = 'Out of Stock';
        } elseif ($product->total_quantity > $product->maximum_stock) {
            $product->status = 'Over Stock';
        } elseif ($product->total_quantity <= ($product->minimum_stock + 30)) {
            $product->status = 'Low Stock';
        } else {
            $product->status = 'In Stock';
        }
    });

    return $products;
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



