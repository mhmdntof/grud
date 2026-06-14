<?php

namespace App\Http\Controllers;

use App\Services\DepartmentService;
use Illuminate\Support\Facades\Auth;
use App\Models\Request;

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


//جلب مواد القسم 

public function getDepartmentProducts(
    string $departmentName,
    string $type
)
{
    return response()->json([
        'data' => $this->departmentService
            ->getDepartmentProducts(
                $departmentName,
                $type
            )
    ]);
}




}