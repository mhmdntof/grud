<?php

namespace App\Http\Controllers;

use App\Http\Requests\Prescription\StorePrescriptionRequest;
use App\Http\Requests\Prescription\UpdatePrescriptionStatusRequest;
use App\Services\PrescriptionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PrescriptionController extends Controller
{
    public function __construct(
        private PrescriptionService $prescriptionService
    ) {}

    /**
     * إنشاء وصفة طبية جديدة
     * POST /api/prescriptions
     *
     * متاح لـ: جميع رؤساء الأقسام
     */
    public function store(StorePrescriptionRequest $request): JsonResponse
    {
        $user = $request->user();

        $result = $this->prescriptionService->create(
            $request->validated(),
            $user->id,
            $user->department_id
        );

        if (!$result['success']) {
            return $this->sendError(
                $result['error'],
                [],
                500
            );
        }

        return $this->sendResponse(
            $result['prescription'],
            'تم إنشاء الوصفة الطبية بنجاح',
            201
        );
    }

    /**
     * جلب وصافاتي فقط
     * GET /api/prescriptions/my-prescriptions
     *
     * متاح لـ: جميع رؤساء الأقسام
     */
    public function myPrescriptions(Request $request): JsonResponse
    {
        $user = $request->user();

        $filters = $request->only([
            'status',
            'from_date',
            'to_date',
            'search',
            'per_page'
        ]);

        // إضافة فلتر تلقائي لـ created_by
        $filters['created_by'] = $user->id;

        $result = $this->prescriptionService->getAll($filters);

        return $this->sendResponse(
            $result,
            'تم جلب وصافاتي بنجاح'
        );
    }

    /**
     * جلب كل الوصفات (للصيدلية فقط)
     * GET /api/prescriptions/all
     *
     * متاح لـ: الصيدلية فقط (عبر Middleware)
     */
    public function index(Request $request): JsonResponse
    {
        $filters = $request->only([
            'status',
            'department_id',
            'from_date',
            'to_date',
            'search',
            'per_page'
        ]);

        $result = $this->prescriptionService->getAll($filters);

        return $this->sendResponse(
            $result,
            'تم جلب جميع الوصفات بنجاح'
        );
    }

    /**
     * جلب وصفة واحدة بالتفصيل
     * GET /api/prescriptions/{id}
     *
     * متاح لـ: جميع رؤساء الأقسام (لوصفاتهم فقط)
     */
    public function show(int $id, Request $request): JsonResponse
    {
        $user = $request->user();

        $result = $this->prescriptionService->getById($id);

        if (!$result['success']) {
            return $this->sendError('الوصفة غير موجودة', [], 404);
        }

        // التحقق من أن المستخدم هو صاحب الوصفة أو من الصيدلية
        $prescription = $result['prescription'];

        if ($prescription->created_by !== $user->id &&
            !$this->isPharmacyUser($user)) {
            return $this->sendError('غير مصرح لك بعرض هذه الوصفة', [], 403);
        }

        return $this->sendResponse(
            $prescription,
            'تم جلب الوصفة بنجاح'
        );
    }

    /**
     * تحديث حالة الوصفة
     * PATCH /api/prescriptions/{id}/status
     *
     * متاح لـ: الصيدلية فقط (عبر Middleware)
     */
    public function updateStatus(
        int $id,
        UpdatePrescriptionStatusRequest $request
    ): JsonResponse {
        $result = $this->prescriptionService->updateStatus(
            $id,
            $request->validated(),
            $request->user()->id
        );

        if (!$result['success']) {
            return $this->sendError(
                $result['error'],
                [],
                400
            );
        }

        return $this->sendResponse(
            $result['prescription'],
            'تم تحديث حالة الوصفة بنجاح'
        );
    }

    /**
     * حذف وصفة
     * DELETE /api/prescriptions/{id}
     *
     * متاح لـ: صاحب الوصفة فقط (إذا كانت new)
     */
    public function destroy(int $id, Request $request): JsonResponse
    {
        $result = $this->prescriptionService->delete(
            $id,
            $request->user()->id
        );

        if (!$result['success']) {
            return $this->sendError(
                $result['error'],
                [],
                403
            );
        }

        return $this->sendResponse(
            null,
            $result['message']
        );
    }

    /**
     * إحصائيات وصافاتي
     * GET /api/prescriptions/my-statistics
     *
     * متاح لـ: جميع رؤساء الأقسام
     */
    public function myStatistics(Request $request): JsonResponse
    {
        $user = $request->user();

        $statistics = $this->prescriptionService->getStatistics(
            null, // لا فلتر للقسم
            $user->id // فقط وصافاتي
        );

        return $this->sendResponse(
            $statistics,
            'تم جلب الإحصائيات بنجاح'
        );
    }

    /**
     * إحصائيات كل الوصفات
     * GET /api/prescriptions/statistics
     *
     * متاح لـ: الصيدلية فقط (عبر Middleware)
     */
    public function statistics(Request $request): JsonResponse
    {
        $departmentId = $request->query('department_id');

        $statistics = $this->prescriptionService->getStatistics($departmentId);

        return $this->sendResponse(
            $statistics,
            'تم جلب الإحصائيات بنجاح'
        );
    }

    /**
     * التحقق من أن المستخدم من الصيدلية
     */
    private function isPharmacyUser($user): bool
    {
        if (!$user || $user->role->name !== 'department_head') {
            return false;
        }

        $pharmacyNames = ['صيدلية', 'pharmacy', 'pharmacy department'];
        return in_array(
            strtolower(trim($user->department->name ?? '')),
            array_map('strtolower', $pharmacyNames)
        );
    }
}
