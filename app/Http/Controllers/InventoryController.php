<?php

namespace App\Http\Controllers;

use App\Http\Requests\Inventory\CreateInventorySessionRequest;
use App\Http\Requests\Inventory\RecordActualQuantityRequest;
use App\Services\InventoryService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class InventoryController extends Controller
{
    public function __construct(
        private InventoryService $inventoryService
    ) {}

    /**
     * عرض جلسات الجرد
     */
    public function index(Request $request): JsonResponse
    {
        $filters = $request->only(['status', 'from_date', 'to_date', 'per_page']);
        $result = $this->inventoryService->getSessions($filters);
        return $this->sendResponse($result, 'Inventory sessions retrieved successfully');
    }

    /**
     * إنشاء جلسة جرد جديدة
     */
    public function store(CreateInventorySessionRequest $request): JsonResponse
    {
        $result = $this->inventoryService->createSession(
            $request->validated(),
            $request->user()->id
        );
        return $this->sendResponse($result, 'Inventory session created successfully', 201);
    }

    /**
     * عرض تفاصيل جلسة جرد
     */
    public function show(int $id): JsonResponse
    {
        $result = $this->inventoryService->getSessionById($id);
        return $this->sendResponse($result);
    }

    /**
     * تسجيل الكميات الفعلية
     */
    public function recordQuantities(int $id, RecordActualQuantityRequest $request): JsonResponse
    {
        $result = $this->inventoryService->recordActualQuantities(
            $id,
            $request->validated()['items']
        );
        return $this->sendResponse($result, 'Quantities recorded successfully');
    }

    /**
     * إكمال جلسة الجرد
     */
    public function complete(int $id): JsonResponse
    {
        $result = $this->inventoryService->completeSession($id);
        return $this->sendResponse($result, 'Inventory session completed successfully');
    }

    /**
     * اعتماد جلسة الجرد وتطبيق التعديلات
     */
    public function approve(int $id, Request $request): JsonResponse
    {
        $result = $this->inventoryService->approveSession(
            $id,
            $request->user()->id
        );
        return $this->sendResponse($result, 'Inventory session approved and adjustments applied');
    }
}
