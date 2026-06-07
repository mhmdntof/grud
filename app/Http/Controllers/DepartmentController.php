<?php

namespace App\Http\Controllers;

use App\Services\DepartmentService;

class DepartmentController extends Controller
{
    protected DepartmentService $departmentService;

    public function __construct(
        DepartmentService $departmentService
    ) {
        $this->departmentService = $departmentService;
    }

    public function getDepartments()
    {
        return response()->json([
            'data' => $this->departmentService
                ->getDepartments()
        ]);
    }
}