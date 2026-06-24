<?php

namespace App\Services;

use App\Models\InventorySession;
use App\Models\InventoryItem;
use App\Models\InventoryAdjustment;
use App\Models\Product;
use App\Models\Batch;
use App\Models\StockMovement;
use Illuminate\Support\Facades\DB;

class InventoryService
{
    /**
     * إنشاء جلسة جرد جديدة
     */
    public function createSession(array $data, int $userId): array
    {
        return DB::transaction(function () use ($data, $userId) {
            $session = InventorySession::create([
                'name' => $data['name'],
                'inventory_date' => $data['inventory_date'],
                'created_by' => $userId,
                'status' => 'in_progress',
                'notes' => $data['notes'] ?? null,
            ]);

            // جلب المنتجات المحددة أو جميع المنتجات
            $productsQuery = Product::query()
                ->with(['batches' => function ($q) {
                    $q->where('quantity', '>', 0);
                }]);

            if (!empty($data['product_ids'])) {
                $productsQuery->whereIn('id', $data['product_ids']);
            }

            $products = $productsQuery->get();

            // إنشاء inventory items لكل منتج ودفعة
            foreach ($products as $product) {
                if ($product->batches->isEmpty()) {
                    // منتج بدون دفعات - استخدم total_quantity
                    InventoryItem::create([
                        'inventory_session_id' => $session->id,
                        'product_id' => $product->id,
                        'batch_id' => null,
                        'system_quantity' => $product->total_quantity,
                        'actual_quantity' => null,
                        'difference' => 0,
                        'variance_type' => 'match',
                    ]);
                } else {
                    // منتج بدفعات - أنشئ item لكل دفعة
                    foreach ($product->batches as $batch) {
                        InventoryItem::create([
                            'inventory_session_id' => $session->id,
                            'product_id' => $product->id,
                            'batch_id' => $batch->id,
                            'system_quantity' => $batch->quantity,
                            'actual_quantity' => null,
                            'difference' => 0,
                            'variance_type' => 'match',
                        ]);
                    }
                }
            }

            return [
                'session' => $session->load([
                    'items.product:id,name,code,unit',
                    'items.batch:id,batch_number,expire_date',
                    'creator:id,name'
                ])
            ];
        });
    }

    /**
     * تسجيل الكميات الفعلية
     */
    public function recordActualQuantities(int $sessionId, array $items): array
    {
        return DB::transaction(function () use ($sessionId, $items) {
            $session = InventorySession::lockForUpdate()->findOrFail($sessionId);

            if (!in_array($session->status, ['in_progress', 'draft'])) {
                throw new \Exception('لا يمكن تعديل جلسة جرد مكتملة أو معتمدة');
            }

            foreach ($items as $itemData) {
                $item = InventoryItem::lockForUpdate()
                    ->where('id', $itemData['id'])
                    ->where('inventory_session_id', $sessionId)
                    ->first();

                if (!$item) {
                    throw new \Exception('المادة غير موجودة في هذه الجلسة');
                }

                $item->update([
                    'actual_quantity' => $itemData['actual_quantity'],
                    'adjustment_notes' => $itemData['adjustment_notes'] ?? null,
                ]);

                // حساب الفرق ونوع التباين
                $item->calculateVariance();
                $item->save();
            }

            return [
                'session' => $session->fresh([
                    'items.product:id,name,code,unit',
                    'items.batch:id,batch_number,expire_date',
                ])
            ];
        });
    }

    /**
     * إكمال جلسة الجرد
     */
    public function completeSession(int $sessionId): array
    {
        return DB::transaction(function () use ($sessionId) {
            $session = InventorySession::lockForUpdate()->findOrFail($sessionId);

            if ($session->status !== 'in_progress') {
                throw new \Exception('يجب أن تكون الجلسة قيد التنفيذ');
            }

            // التحقق من أن جميع المواد تم عدّها
            $unrecordedItems = $session->items()
                ->whereNull('actual_quantity')
                ->count();

            if ($unrecordedItems > 0) {
                throw new \Exception("يوجد {$unrecordedItems} مادة لم يتم عدّها بعد");
            }

            $session->update(['status' => 'completed']);

            return [
                'session' => $session->load([
                    'items.product:id,name,code,unit',
                    'items.batch:id,batch_number,expire_date',
                ]),
                'statistics' => $this->getSessionStatistics($session)
            ];
        });
    }

    /**
     * اعتماد جلسة الجرد وتطبيق التعديلات
     */
    public function approveSession(int $sessionId, int $userId): array
    {
        return DB::transaction(function () use ($sessionId, $userId) {
            $session = InventorySession::lockForUpdate()->findOrFail($sessionId);

            if ($session->status !== 'completed') {
                throw new \Exception('يجب أن تكون الجلسة مكتملة قبل الاعتماد');
            }

            $adjustments = [];

            // تطبيق التعديلات على المخزون
            foreach ($session->items as $item) {
                if ($item->variance_type === 'match') {
                    continue; // لا تعديل مطلوب
                }

                $difference = $item->difference;

                if ($item->variance_type === 'surplus') {
                    // فائض: زيادة المخزون
                    $product = Product::lockForUpdate()->find($item->product_id);
                    $product->increment('total_quantity', $difference);

                    if ($item->batch_id) {
                        $batch = Batch::lockForUpdate()->find($item->batch_id);
                        $batch->increment('quantity', $difference);
                    }

                    // تسجيل حركة
                    StockMovement::create([
                        'user_id' => $userId,
                        'product_id' => $item->product_id,
                        'batch_id' => $item->batch_id,
                        'type' => 'in',
                        'quantity' => $difference,
                        'notes' => "تعديل جرد - جلسة: {$session->name} - فائض"
                    ]);

                } elseif ($item->variance_type === 'shortage') {
                    // عجز: نقص المخزون
                    $product = Product::lockForUpdate()->find($item->product_id);
                    $product->decrement('total_quantity', abs($difference));

                    if ($item->batch_id) {
                        $batch = Batch::lockForUpdate()->find($item->batch_id);
                        $batch->decrement('quantity', abs($difference));
                    }

                    // تسجيل حركة
                    StockMovement::create([
                        'user_id' => $userId,
                        'product_id' => $item->product_id,
                        'batch_id' => $item->batch_id,
                        'type' => 'adjustment',
                        'quantity' => abs($difference),
                        'notes' => "تعديل جرد - جلسة: {$session->name} - عجز"
                    ]);
                }

                // تسجيل التعديل
                $adjustment = InventoryAdjustment::create([
                    'inventory_session_id' => $session->id,
                    'product_id' => $item->product_id,
                    'type' => $item->variance_type === 'surplus' ? 'surplus' : 'shortage',
                    'quantity' => abs($difference),
                    'approved_by' => $userId,
                    'approved_at' => now(),
                    'notes' => $item->adjustment_notes,
                ]);

                $adjustments[] = $adjustment;
            }

            $session->update([
    'status' => 'approved',
    'approved_by' => $userId,
    'approved_at' => now(),
]);

return [
    'session' => $session->fresh([
        'items.product:id,name,code,unit',
        'items.batch:id,batch_number,expire_date',
        'creator:id,name',
        'approver:id,name'
    ]),
    'adjustments' => $adjustments,
    'statistics' => $this->getSessionStatistics($session)
];
        });
    }

    /**
     * جلب جلسات الجرد
     */
    public function getSessions(array $filters = []): array
    {
        $query = InventorySession::with(['creator:id,name', 'approver:id,name']);

        if (!empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (!empty($filters['from_date'])) {
            $query->whereDate('inventory_date', '>=', $filters['from_date']);
        }

        if (!empty($filters['to_date'])) {
            $query->whereDate('inventory_date', '<=', $filters['to_date']);
        }

        $query->orderBy('inventory_date', 'desc');
        $perPage = $filters['per_page'] ?? 15;
        $sessions = $query->paginate($perPage);

        return [
            'sessions' => \App\Http\Resources\InventorySessionResource::collection($sessions),
            'pagination' => [
                'current_page' => $sessions->currentPage(),
                'last_page' => $sessions->lastPage(),
                'per_page' => $sessions->perPage(),
                'total' => $sessions->total(),
            ]
        ];
    }

    /**
     * جلب تفاصيل جلسة جرد
     */
    public function getSessionById(int $sessionId): array
    {
        $session = InventorySession::with([
            'items.product:id,name,code,unit',
            'items.batch:id,batch_number,expire_date',
            'creator:id,name',
            'approver:id,name',
            'adjustments.product:id,name'
        ])->findOrFail($sessionId);

        return [
            'session' => new \App\Http\Resources\InventorySessionResource($session),
            'items' => \App\Http\Resources\InventoryItemResource::collection($session->items),
            'statistics' => $this->getSessionStatistics($session)
        ];
    }

    /**
     * إحصائيات جلسة الجرد
     */
    private function getSessionStatistics(InventorySession $session): array
    {
        $items = $session->items;

        return [
            'total_items' => $items->count(),
            'matched' => $items->where('variance_type', 'match')->count(),
            'surplus' => $items->where('variance_type', 'surplus')->count(),
            'shortage' => $items->where('variance_type', 'shortage')->count(),
            'damaged' => $items->where('variance_type', 'damaged')->count(),
            'total_surplus_quantity' => $items->where('variance_type', 'surplus')->sum('difference'),
            'total_shortage_quantity' => abs($items->where('variance_type', 'shortage')->sum('difference')),
        ];
    }
}
