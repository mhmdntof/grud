<?php

namespace App\Services;

use App\Models\Product;
use App\Models\Request;
use Illuminate\Support\Facades\DB;

class DepartmentHeadService
{
    public function submitRequest(array $data, int $userId): array
    {
        return DB::transaction(function () use ($data, $userId) {
            $user = \App\Models\User::findOrFail($userId);

            // التحقق من أن المستخدم لديه قسم
            if (!$user->department_id) {
                throw new \Exception('المستخدم غير مرتبط بقسم');
            }

            // التحقق من أن المنتج موجود وله مخزون (اختياري: نسمح بطلب حتى لو المخزون صفر)
            $product = Product::findOrFail($data['product_id']);

            $request = Request::create([
                'user_id' => $userId,
                'department_id' => $user->department_id,
                'product_id' => $data['product_id'],
                'requested_quantity' => $data['requested_quantity'],
                'type' => $data['type'],
                'status' => 'pending',
                'needed_by' => $data['needed_by'] ?? null,
                'recurring_frequency' => $data['recurring_frequency'] ?? null,
            ]);

            return [
                'request' => [
                    'id' => $request->id,
                    'type' => $request->type,
                    'status' => $request->status,
                    'requested_quantity' => $request->requested_quantity,
                    'needed_by' => $request->needed_by,
                    'recurring_frequency' => $request->recurring_frequency,
                    'created_at' => $request->created_at,

                // المنتج - بدون total_quantity!
                'product' => [
                'id' => $request->product->id,
                'name' => $request->product->name,
                'code' => $request->product->code,
                'unit' => $request->product->unit,
                ],

                // القسم
                'department' => [
                'id' => $request->department->id,
                'name' => $request->department->name,
                ],
            ],
    ];
        });
    }

    public function getMyRequests(int $userId, array $filters = []): array
    {
        $query = Request::where('user_id', $userId)
            ->with(['product:id,name,code,unit', 'department:id,name']);

        // تصفية حسب الحالة
        if (!empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        // تصفية حسب النوع
        if (!empty($filters['type'])) {
            $query->where('type', $filters['type']);
        }

        $query->orderBy('created_at', 'desc');

        $perPage = $filters['per_page'] ?? 15;
        $requests = $query->paginate($perPage);

        return [
            'requests' => \App\Http\Resources\RequestResource::collection($requests),
            'pagination' => [
                'current_page' => $requests->currentPage(),
                'last_page' => $requests->lastPage(),
                'per_page' => $requests->perPage(),
                'total' => $requests->total(),
            ],
        ];
    }

    public function cancelRequest(int $requestId, int $userId): array
    {
        return DB::transaction(function () use ($requestId, $userId) {
            // ✅ قفل الصف
            $request = Request::lockForUpdate()
                ->where('id', $requestId)
                ->where('user_id', $userId)
                ->first();

            if (!$request) {
                throw new \Exception('الطلب غير موجود أو لا تملك صلاحية الوصول إليه');
            }

            if (!in_array($request->status, ['pending', 'approved'])) {
                throw new \Exception('لا يمكن إلغاء طلب تمت معالجته');
            }

            $request->update(['status' => 'cancelled']);

            $request->load(['product:id,name,code,unit', 'department:id,name']);

            return [
                'request' => [
                    'id' => $request->id,
                    'type' => $request->type,
                    'status' => $request->status,
                    'requested_quantity' => $request->requested_quantity,
                    'product' => [
                        'id' => $request->product->id,
                        'name' => $request->product->name,
                        'code' => $request->product->code,
                        'unit' => $request->product->unit,
                    ],
                    'department' => [
                        'id' => $request->department->id,
                        'name' => $request->department->name,
                    ],
                ],
            ];
        });
    }
}
