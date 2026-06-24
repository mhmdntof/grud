<?php
namespace App\Http\Controllers;

use App\Http\Requests\DepartmentHeader\StoreRequest;
use App\Http\Requests\DepartmentHeader\UpdateRequest;
use App\Http\Requests\DepartmentHeader\ConfirmReceiptRequest;
use App\Http\Requests\DepartmentHeader\ReturnRequest;
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
        return $this->sendResponse($result, 'Request submitted', 201);
    }

    public function index(Request $request): JsonResponse
    {
        $result = $this->departmentHeadService->getMyRequests(
            $request->user()->id,
            $request->only(['status', 'type', 'per_page'])
        );
        return $this->sendResponse($result);
    }

    public function cancel(int $id, Request $request): JsonResponse
    {
        $result = $this->departmentHeadService->cancelRequest(
            $id,
            $request->user()->id
        );
        return $this->sendResponse($result, 'Request cancelled');
    }

    public function update(int $id, UpdateRequest $request): JsonResponse
    {
        $result = $this->departmentHeadService->updateRequest(
            $id,
            $request->validated(),
            $request->user()->id
        );

        return $this->sendResponse($result, 'Request updated successfully');
    }

    public function availableProducts(Request $request): JsonResponse
    {
        $result = $this->departmentHeadService->getAvailableProducts(
            $request->only(['search', 'type', 'per_page', 'page'])
        );

        return $this->sendResponse($result);
    }

    public function confirmReceipt(int $id, ConfirmReceiptRequest $request): JsonResponse
    {
        $result = $this->departmentHeadService->confirmReceipt(
            $id,
            $request->validated(),
            $request->user()->id
        );

        return $this->sendResponse($result, 'Receipt confirmed successfully');
    }

    public function returnRequest(ReturnRequest $request): JsonResponse
    {
        $result = $this->departmentHeadService->returnRequest(
            $request->validated(),
            $request->user()->id
        );

        return $this->sendResponse($result, 'Return request created successfully', 201);
    }
}
