<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PrescriptionResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            // المعرف كـ String (كما يريد Flutter)
            'id' => (string) $this->id,

            // بيانات المريض (camelCase كما يريد Flutter)
            'patientName' => $this->patient_name,
            'patientAge' => $this->patient_age,
            'patientGender' => $this->patient_gender,

            // بيانات الطبيب
            'doctorName' => $this->doctor_name,
            'condition' => $this->medical_condition,
            'notes' => $this->notes,

            // الحالة: new → newRx (كما يريد Flutter)
            'status' => $this->status === 'new' ? 'newRx' : $this->status,

            // التاريخ بصيغة ISO 8601 (كما يريد Flutter)
            'date' => $this->created_at?->format('Y-m-d\TH:i:s'),

            // الأدوية كـ String مفصولة بفاصلة (كما يريد Flutter)
            'medications' => $this->whenLoaded('medicines', function () {
                return $this->medicines->pluck('medicine_name')->implode(', ');
            }),

            // الأدوية كتفاصيل كاملة (للاستخدام المتقدم)
            'medicines' => PrescriptionMedicineResource::collection(
                $this->whenLoaded('medicines')
            ),

            // القسم
            'department' => [
                'id' => (string) ($this->department?->id ?? ''),
                'name' => $this->department?->name ?? '',
            ],

            // الطبيب الذي أنشأ الوصفة
            'createdBy' => [
                'id' => (string) ($this->creator?->id ?? ''),
                'name' => $this->creator?->name ?? '',
            ],

            // الصيدلي الذي صدق الوصفة (إن وجد)
            'approvedBy' => $this->when(
                $this->approved_by,
                [
                    'id' => (string) ($this->approver?->id ?? ''),
                    'name' => $this->approver?->name ?? '',
                ]
            ),

            // سبب الرفض (إن وجد)
            'rejectionReason' => $this->when(
                $this->status === 'rejected',
                $this->rejection_reason
            ),
        ];
    }
}
