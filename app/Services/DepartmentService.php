<?php

namespace App\Services;

use App\Models\Department;

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



    
}