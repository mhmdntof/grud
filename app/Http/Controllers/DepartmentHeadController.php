<?php

namespace App\Http\Controllers;

use App\Http\Requests\DepartmentHeader\StoreRequest;
use App\Services\DepartmentHeadService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DepartmentHeadController extends Controller
{
    public function __construct(
        private DepartmentHeadService $departmentHeadService
    ) {}

    public function store(StoreRequest $request): JsonResponse
    {
        $result = $this->departmentHeadService->submitRequest(
            $request->validated(),
            $request->user()->id
        );

        return $this->sendResponse($result, 'Request submitted successfully', 201);
    }

    public function index(Request $request): JsonResponse
    {
        $filters = $request->only(['status', 'type', 'per_page']);

        $result = $this->departmentHeadService->getMyRequests(
            $request->user()->id,
            $filters
        );

        return $this->sendResponse($result, 'Requests retrieved successfully');
    }

    public function cancel(int $id, Request $request): JsonResponse
    {
        $result = $this->departmentHeadService->cancelRequest(
            $id,
            $request->user()->id
        );

        return $this->sendResponse($result, 'Request cancelled successfully');
    }
}
