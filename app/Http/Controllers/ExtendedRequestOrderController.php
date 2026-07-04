<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Requests\CreateRecurringRequestOrderRequest;
use App\Http\Requests\Requests\UpdateRequestOrderRequest;

use App\Services\ExtendedRequestOrderService;
use App\Http\Resources\RequestOrderResource;
use App\Models\RequestOrder;

class ExtendedRequestOrderController extends Controller
{
    protected $extendedService;

    public function __construct(ExtendedRequestOrderService $extendedService)
    {
        $this->extendedService = $extendedService;
    }

    /**
     * عرض طلباتي - لرئيس القسم
     * GET /api/my-requests
     */
    public function myRequests(Request $request)
    {
        $user = $request->user();
        $filters = $request->only(['status', 'type', 'per_page']);

        $result = $this->extendedService->getMyRequests(
            $user->id,
            $filters
        );

        return response()->json([
            'success' => true,
            'message' => 'تم جلب طلباتي بنجاح',
            'data' => $result,
        ]);
    }


/**
 * تعديل طلب
 * PUT /api/request-orders/{id}
 */
public function update(int $id, UpdateRequestOrderRequest $request)
{
    $order = $this->extendedService->updateRequestOrder(
        $id,
        $request->validated(),
        $request->user()->id
    );

    return response()->json([
        'success' => true,
        'message' => 'تم تحديث الطلب بنجاح',
        'data' => $order,
    ]);
}

    /**
     * إلغاء طلب
     * DELETE /api/my-requests/{id}
     */
    public function cancel(int $id, Request $request)
    {
        $result = $this->extendedService->cancelRequest(
            $id,
            $request->user()->id
        );

        return response()->json([
            'success' => true,
            'message' => 'تم إلغاء الطلب بنجاح',
            'data' => $result,
        ]);
    }

    public function createRecurring(CreateRecurringRequestOrderRequest $request)
{
    $order = $this->extendedService->createRecurringRequest(
        $request->validated(),
        $request->user()->id
    );

    return response()->json([
        'success' => true,
        'message' => 'تم إنشاء الطلب الدوري بنجاح',
        'data' => $order,
    ], 201);
}

/**
 * استعراض القوالب الدورية النشطة (وليس النسخ)
 * GET /api/request-orders/recurring
 */
public function myRecurringRequests(Request $request)
{
    $user = $request->user();
    $filters = $request->only(['frequency', 'per_page']);

    $result = $this->extendedService->getMyRecurringRequests(
        $user->id,
        $filters
    );

    return response()->json([
        'success' => true,
        'message' => 'تم جلب الطلبات الدورية بنجاح',
        'data' => $result,
    ]);
}

/**
 * استعراض جميع النسخ لقالب معين
 * GET /api/request-orders/recurring/{id}/instances
 */
public function recurringInstances(int $id, Request $request)
{
    $result = $this->extendedService->getRecurringInstances(
        $id,
        $request->user()->id
    );

    return response()->json([
        'success' => true,
        'message' => 'تم جلب نسخ القالب الدوري بنجاح',
        'data' => $result,
    ]);
}

/**
 * إلغاء طلب دوري
 * DELETE /api/request-orders/recurring/{id}
 */
public function cancelRecurring(int $id, Request $request)
{
    $result = $this->extendedService->cancelRecurringRequest(
        $id,
        $request->user()->id
    );

    return response()->json([
        'success' => true,
        'message' => 'تم إلغاء الطلب الدوري بنجاح',
        'data' => $result,
    ]);
}

/**
 * تفاصيل طلب (باستخدام Resource الخاص بنا)
 * GET /api/request-orders/{id}/details
 */
public function getDetails(int $id)
{
    $order = RequestOrder::with(['items.product', 'department', 'requester'])
        ->findOrFail($id);

    return response()->json([
        'success' => true,
        'message' => 'تم جلب تفاصيل الطلب بنجاح',
        'data' => new RequestOrderResource($order),
    ]);
}
}
