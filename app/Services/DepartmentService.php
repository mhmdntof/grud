<?php

namespace App\Services;

use App\Models\Department;
use App\Models\DepartmentProduct;

class DepartmentService
{
    public function getDepartments()
    {
        return Department::select(
            'id',
            'name'
        )
        ->orderBy('name')
        ->get();
    }


//جلب مواد المستودع 



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
}