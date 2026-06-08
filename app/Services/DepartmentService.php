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

}