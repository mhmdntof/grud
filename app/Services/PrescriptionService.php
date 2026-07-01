<?php

namespace App\Services;

use App\Models\Prescription;
use App\Models\PrescriptionMedicine;
use App\Http\Resources\PrescriptionResource;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Pagination\LengthAwarePaginator;

class PrescriptionService
{
    /**
     * إنشاء وصفة طبية جديدة
     */
    public function create(array $data, int $userId, int $departmentId): array
    {
        try {
            return DB::transaction(function () use ($data, $userId, $departmentId) {
                // إنشاء الوصفة
                $prescription = Prescription::create([
                    'patient_name' => $data['patient_name'],
                    'patient_age' => $data['patient_age'] ?? null,
                    'patient_gender' => $data['patient_gender'] ?? null,
                    'doctor_name' => $data['doctor_name'],
                    'medical_condition' => $data['medical_condition'],
                    'notes' => $data['notes'] ?? null,
                    'status' => 'new',
                    'department_id' => $departmentId,
                    'created_by' => $userId,
                ]);

                // إضافة الأدوية
                foreach ($data['medicines'] as $medicine) {
                    $prescription->medicines()->create([
                        'medicine_name' => $medicine['medicine_name'],
                        'dosage' => $medicine['dosage'] ?? null,
                        'quantity' => $medicine['quantity'],
                        'unit' => $medicine['unit'],
                        'frequency' => $medicine['frequency'] ?? null,
                        'instructions' => $medicine['instructions'] ?? null,
                    ]);
                }

                // تسجيل العملية في الـ Audit Log
                Log::channel('audit')->info('Prescription created', [
                    'prescription_id' => $prescription->id,
                    'user_id' => $userId,
                    'department_id' => $departmentId,
                    'patient_name' => $prescription->patient_name,
                    'medicines_count' => count($data['medicines']),
                ]);

                // إرجاع الوصفة مع العلاقات
                $prescription->load(['medicines', 'department', 'creator']);

                return [
                    'success' => true,
                    'prescription' => new PrescriptionResource($prescription),
                ];
            });
        } catch (\Exception $e) {
            Log::error('Failed to create prescription', [
                'error' => $e->getMessage(),
                'user_id' => $userId,
                'data' => $data,
            ]);

            return [
                'success' => false,
                'error' => 'فشل في إنشاء الوصفة الطبية',
            ];
        }
    }

    /**
     * جلب قائمة الوصفات مع الفلترة
     */
    public function getAll(array $filters = [], int $perPage = 15): array
    {
        $query = Prescription::with(['medicines', 'department', 'creator', 'approver']);

        // فلترة حسب الحالة
        if (!empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        // فلترة حسب القسم
        if (!empty($filters['department_id'])) {
            $query->where('department_id', $filters['department_id']);
        }

        // فلترة حسب التاريخ
        if (!empty($filters['from_date'])) {
            $query->whereDate('created_at', '>=', $filters['from_date']);
        }

        if (!empty($filters['to_date'])) {
            $query->whereDate('created_at', '<=', $filters['to_date']);
        }

        // البحث بالاسم
        if (!empty($filters['search'])) {
            $search = $filters['search'];
            $query->where(function ($q) use ($search) {
                $q->where('patient_name', 'like', "%{$search}%")
                  ->orWhere('doctor_name', 'like', "%{$search}%")
                  ->orWhere('medical_condition', 'like', "%{$search}%");
            });
        }

        // الترتيب
        $query->orderBy('created_at', 'desc');

        // Pagination
        $prescriptions = $query->paginate($perPage);

        return [
            'prescriptions' => PrescriptionResource::collection($prescriptions),
            'pagination' => [
                'current_page' => $prescriptions->currentPage(),
                'last_page' => $prescriptions->lastPage(),
                'per_page' => $prescriptions->perPage(),
                'total' => $prescriptions->total(),
            ],
        ];
    }

    /**
     * جلب وصفة واحدة بالتفصيل
     */
    public function getById(int $id): array
    {
        $prescription = Prescription::with(['medicines', 'department', 'creator', 'approver'])
            ->findOrFail($id);

        return [
            'success' => true,
            'prescription' => new PrescriptionResource($prescription),
        ];
    }

    /**
     * تحديث حالة الوصفة (موافقة/رفض/معالجة)
     */
    public function updateStatus(int $id, array $data, int $userId): array
    {
        try {
            return DB::transaction(function () use ($id, $data, $userId) {
                $prescription = Prescription::lockForUpdate()->findOrFail($id);

                // التحقق من أن الوصفة لم تتم معالجتها مسبقاً
                if ($prescription->status === 'processed') {
                    return [
                        'success' => false,
                        'error' => 'الوصفة تمت معالجتها مسبقاً',
                    ];
                }

                // تحديث الحالة
                $prescription->update([
                    'status' => $data['status'],
                    'rejection_reason' => $data['rejection_reason'] ?? null,
                    'approved_by' => $userId,
                ]);

                // تسجيل العملية
                Log::channel('audit')->info('Prescription status updated', [
                    'prescription_id' => $id,
                    'user_id' => $userId,
                    'old_status' => $prescription->getOriginal('status'),
                    'new_status' => $data['status'],
                ]);

                // إعادة تحميل العلاقات
                $prescription->load(['medicines', 'department', 'creator', 'approver']);

                return [
                    'success' => true,
                    'prescription' => new PrescriptionResource($prescription),
                ];
            });
        } catch (\Exception $e) {
            Log::error('Failed to update prescription status', [
                'error' => $e->getMessage(),
                'prescription_id' => $id,
                'user_id' => $userId,
            ]);

            return [
                'success' => false,
                'error' => 'فشل في تحديث حالة الوصفة',
            ];
        }
    }

    /**
     * حذف وصفة (Soft Delete)
     */
    public function delete(int $id, int $userId): array
    {
        try {
            $prescription = Prescription::findOrFail($id);

            // التحقق من أن المستخدم هو صاحب الوصفة
            if ($prescription->created_by !== $userId) {
                return [
                    'success' => false,
                    'error' => 'لا تملك صلاحية حذف هذه الوصفة',
                ];
            }

            // التحقق من أن الوصفة لم تتم معالجتها
            if ($prescription->status !== 'new') {
                return [
                    'success' => false,
                    'error' => 'لا يمكن حذف وصفة تمت معالجتها',
                ];
            }

            $prescription->delete();

            Log::channel('audit')->info('Prescription deleted', [
                'prescription_id' => $id,
                'user_id' => $userId,
            ]);

            return [
                'success' => true,
                'message' => 'تم حذف الوصفة بنجاح',
            ];
        } catch (\Exception $e) {
            Log::error('Failed to delete prescription', [
                'error' => $e->getMessage(),
                'prescription_id' => $id,
            ]);

            return [
                'success' => false,
                'error' => 'فشل في حذف الوصفة',
            ];
        }
    }

    /**
 * إحصائيات الوصفات
 */
public function getStatistics(?int $departmentId = null, ?int $userId = null): array
{
    $query = Prescription::query();

    if ($departmentId) {
        $query->where('department_id', $departmentId);
    }

    if ($userId) {
        $query->where('created_by', $userId);
    }

    return [
        'total' => $query->count(),
        'new' => (clone $query)->where('status', 'new')->count(),
        'processed' => (clone $query)->where('status', 'processed')->count(),
        'rejected' => (clone $query)->where('status', 'rejected')->count(),
    ];
}
}
