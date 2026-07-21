<?php

namespace App\Services;

use App\Models\RequestOrder;
use App\Models\RequestOrderItem;

use App\Http\Resources\RequestOrderResource;
use App\Http\Resources\RecurringRequestOrderResource;

use App\Http\Requests\Requests\UpdateRequestOrderRequest;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

use App\Models\Product;
class ExtendedRequestOrderService
{
    /**
     * جلب طلبات رئيس قسم معين
     */
    public function getMyRequests(int $userId, array $filters = []): array
    {
        $query = RequestOrder::where('requested_by', $userId)
            ->with(['items.product', 'department', 'requester']);

        // فلترة حسب الحالة
        if (!empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        // فلترة حسب النوع
        if (!empty($filters['type'])) {
            $query->where('request_type', $filters['type']);
        }

        $query->orderBy('created_at', 'desc');

        $perPage = $filters['per_page'] ?? 15;
        $requests = $query->paginate($perPage);

        return [
            'requests' => RequestOrderResource::collection($requests),
            'pagination' => [
                'current_page' => $requests->currentPage(),
                'last_page' => $requests->lastPage(),
                'per_page' => $requests->perPage(),
                'total' => $requests->total(),
            ],
        ];
    }


/**
 * تعديل طلب (فقط إذا كان pending وصاحب الطلب)
 */
public function updateRequestOrder(int $orderId, array $data, int $userId)
    {
        // ✅ 1. Race Conditions Protection
        $order = RequestOrder::where('id', $orderId)
            ->lockForUpdate()  // ← قفل الصف!
            ->with('items')
            ->firstOrFail();

        // ✅ 2. التحقق من الصلاحيات
        if ($order->requested_by !== $userId) {
            throw new \Exception('لا تملك صلاحية تعديل هذا الطلب');
        }

        // ✅ 3. التحقق من أنه ليس قالب دوري
        if ($order->is_template) {
            throw new \Exception('لا يمكن تعديل القوالب الدورية من هنا');
        }

        // ✅ 4. التحقق من أنه ليس نسخة دورية
        if ($order->parent_id !== null) {
            throw new \Exception('لا يمكن تعديل نسخ الطلبات الدورية');
        }

        // ✅ 5. التحقق من الحالة
        if ($order->status !== 'pending') {
            throw new \Exception('لا يمكن تعديل طلب تمت معالجته');
        }

        return DB::transaction(function () use ($order, $data, $userId) {
            // ✅ 6. حفظ البيانات القديمة للتتبع
            $oldData = [
                'request_type' => $order->request_type,
                'items' => $order->items->map(function ($item) {
                    return [
                        'product_id' => $item->product_id,
                        'quantity' => $item->quantity,
                    ];
                })->toArray(),
            ];

            // ✅ 7. Duplicate Items Check
            if (isset($data['items'])) {
                $productIds = collect($data['items'])->pluck('product_id');
                if ($productIds->duplicates()->isNotEmpty()) {
                    throw new \Exception('لا يمكن إضافة نفس المنتج مرتين');
                }
            }

            // ✅ 8. Maximum Quantity Check
            if (isset($data['items'])) {
                foreach ($data['items'] as $item) {
                    $product = Product::findOrFail($item['product_id']);
                    if ($item['quantity'] > $product->maximum_stock) {
                        throw new \Exception("الكمية تتجاوز الحد الأقصى لـ {$product->name} (الحد الأقصى: {$product->maximum_stock})");
                    }
                }
            }

            // ✅ 9. تحديث request_type
            if (isset($data['request_type'])) {
                $order->update(['request_type' => $data['request_type']]);
            }

            // ✅ 10. تحديث المواد
            if (isset($data['items'])) {
                $order->items()->delete();

                foreach ($data['items'] as $item) {
                    RequestOrderItem::create([
                        'request_order_id' => $order->id,
                        'product_id' => $item['product_id'],
                        'quantity' => $item['quantity'],
                    ]);
                }
            }

            $order->load(['items.product', 'department', 'requester']);

            // ✅ 11. Audit Logging
            Log::info('Request Order Updated', [
                'order_id' => $order->id,
                'user_id' => $userId,
                'old_data' => $oldData,
                'new_data' => $data,
                'timestamp' => now()->toDateTimeString(),
            ]);

            return new RequestOrderResource($order);
        });
    }    /**
     * إلغاء طلب (فقط إذا كان pending)
     */
    public function cancelRequest(int $requestId, int $userId)
    {
        $request = RequestOrder::where('id', $requestId)
            ->where('requested_by', $userId)
            ->first();

        if (!$request) {
            throw new \Exception('الطلب غير موجود أو لا تملك صلاحية الوصول إليه');
        }

        if ($request->status !== 'pending') {
            throw new \Exception('لا يمكن إلغاء طلب تمت معالجته');
        }

        $request->update(['status' => 'cancelled']);
        $request->load(['items.product', 'department', 'requester']);

        return new RequestOrderResource($request);
    }


/**
 * إنشاء طلب دوري
 */
public function createRecurringRequest(array $data, int $userId)
{
    $user = \App\Models\User::findOrFail($userId);

    return DB::transaction(function () use ($data, $user, $userId) {
        $nextOccurrence = $this->calculateNextOccurrence($data['recurring_frequency']);

        // ✅ إنشاء القالب (Template) فقط
        $template = RequestOrder::create([
            'department_id' => $user->department_id,
            'requested_by' => $userId,
            'request_type' => $data['request_type'],
            'status' => 'active',  // ← القالب دائماً active
            'is_recurring' => true,
            'is_template' => true,  // ← هذا قالب
            'recurring_frequency' => $data['recurring_frequency'],
            'next_occurrence' => $nextOccurrence,
            'is_active' => true,
            'request_frequency' => 'recurring',
        ]);

        // إضافة المواد للقالب
        foreach ($data['items'] as $item) {
            RequestOrderItem::create([
                'request_order_id' => $template->id,
                'product_id' => $item['product_id'],
                'quantity' => $item['quantity'],
            ]);
        }

        $template->load(['items.product', 'department', 'requester']);

        return new RecurringRequestOrderResource($template);
    });
}
/**
 * حساب التاريخ التالي للتكرار
 */
private function calculateNextOccurrence(string $frequency): Carbon
{
    $now = Carbon::now();

    return match($frequency) {
        'daily' => $now->addDay(),
        'weekly' => $now->addWeek(),
        'monthly' => $now->addMonth(),
        default => $now->addWeek(),
    };
}

/**
 * استعراض القوالب الدورية النشطة (وليس النسخ)
 */
public function getMyRecurringRequests(int $userId, array $filters = []): array
{
    $query = RequestOrder::where('requested_by', $userId)
        ->where('is_recurring', true)
        ->where('is_template', true)  // ← القوالب فقط
        ->where('is_active', true)
        ->with(['items.product', 'department', 'requester', 'recurringChildren']);

    if (!empty($filters['frequency'])) {
        $query->where('recurring_frequency', $filters['frequency']);
    }

    $query->orderBy('next_occurrence', 'asc');

    $perPage = $filters['per_page'] ?? 15;
    $requests = $query->paginate($perPage);

    return [
        'requests' => RecurringRequestOrderResource::collection($requests),
        'pagination' => [
            'current_page' => $requests->currentPage(),
            'last_page' => $requests->lastPage(),
            'per_page' => $requests->perPage(),
            'total' => $requests->total(),
        ],
    ];
}

/**
 * استعراض جميع النسخ لقالب معين
 */
public function getRecurringInstances(int $templateId, int $userId): array
{
    $template = RequestOrder::where('id', $templateId)
        ->where('requested_by', $userId)
        ->where('is_template', true)
        ->firstOrFail();

    $instances = RequestOrder::where('parent_id', $templateId)
        ->with(['items.product', 'department'])
        ->orderBy('created_at', 'desc')
        ->get();

    return [
        'template' => new RecurringRequestOrderResource($template),
        'instances' => RequestOrderResource::collection($instances),
        'total_instances' => $instances->count(),
    ];
}

/**
 * إلغاء طلب دوري
 */
public function cancelRecurringRequest(int $requestId, int $userId)
{
    $request = RequestOrder::where('id', $requestId)
        ->where('requested_by', $userId)
        ->where('is_recurring', true)
        ->first();

    if (!$request) {
        throw new \Exception('الطلب الدوري غير موجود أو لا تملك صلاحية الوصول إليه');
    }

    // ✅ التحقق من الحالة
    if ($request->status === 'delivered') {
        throw new \Exception('لا يمكن إلغاء طلب تم تسليمه');
    }

    if ($request->status === 'rejected') {
        throw new \Exception('الطلب مرفوض بالفعل');
    }

    // ✅ إيقاف التكرار
    $request->update([
        'is_active' => false,
        'status' => 'cancelled',  // ✅ تغيير الحالة أيضاً
    ]);

    $request->load(['items.product', 'department', 'requester']);

    return new RecurringRequestOrderResource($request);
}




/*
// نتركها قد نحتاجها للتتبع
// في ExtendedRequestOrderService.php

/**
 * موافقة مدير المستشفى (نسخة موسعة)

public function approveByManagerExtended(int $orderId, int $userId)
{
    $order = RequestOrder::findOrFail($orderId);

    if ($order->status !== 'pending') {
        throw new \Exception('الطلب لا يمكن الموافقة عليه');
    }

    $order->update([
        'status' => 'in_progress',
        'manager_status' => 'approved',
        'manager_approved_by' => $userId,
        'manager_approved_at' => now(),
    ]);

    return $order;
}

/**
 * موافقة مدير المستودع (نسخة موسعة)

public function approveByWarehouseExtended(int $orderId, array $items, int $userId)
{
    $order = RequestOrder::with('items.product')->findOrFail($orderId);

    if ($order->status !== 'in_progress') {
        throw new \Exception('الطلب يجب أن يكون موافق عليه من المدير أولاً');
    }

    DB::transaction(function () use ($order, $items, $userId) {
        foreach ($items as $itemData) {
            $item = RequestOrderItem::findOrFail($itemData['item_id']);

            if ($itemData['approved_quantity'] > $item->quantity) {
                throw new \Exception('الكمية الموافقة لا يمكن أن تتجاوز الكمية المطلوبة');
            }

            $product = Product::lockForUpdate()->findOrFail($item->product_id);

            if ($itemData['approved_quantity'] > $product->total_quantity) {
                throw new \Exception("الكمية المتاحة لـ {$product->name} هي {$product->total_quantity} فقط");
            }

            $item->update(['approved_quantity' => $itemData['approved_quantity']]);
            $product->decrement('total_quantity', $itemData['approved_quantity']);
        }

        $order->update([
            'status' => 'ready_for_delivery',
            'warehouse_status' => 'approved',
            'warehouse_approved_by' => $userId,
            'warehouse_approved_at' => now(),
        ]);
    });

    return $order->fresh(['items.product']);
}
*/



}
