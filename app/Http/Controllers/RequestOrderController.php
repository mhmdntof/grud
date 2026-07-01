<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Requests\Requests\CreateRequestOrderRequest;
use App\Services\Requests\RequestOrderService;
use Illuminate\Support\Facades\Auth;
use App\Http\Requests\RejectRequestOrderRequest;
use App\Http\Requests\ApproveRequestOrderByWarehouseRequest;
use App\Http\Resources\RequestOrderResource;

class RequestOrderController extends Controller
{
    protected $requestOrderService;

    public function __construct(
        RequestOrderService $requestOrderService
    ) {
        $this->requestOrderService = $requestOrderService;
    }

    /**
     * إنشاء طلب جديد
     * POST /api/request-orders
     */
    public function store(CreateRequestOrderRequest $request)
    {
        $order = $this->requestOrderService->create(
            $request->validated(),
            Auth::user()
        );

        return $this->sendResponse(
            new RequestOrderResource($order),
            'تم إنشاء الطلب بنجاح',
            201
        );
    }

    /**
     * عرض طلباتي - لرئيس القسم
     * GET /api/request-orders/my-requests
     */
    public function myRequests(Request $request)
    {
        $user = $request->user();
        $filters = $request->only(['status', 'type', 'per_page']);

        $result = $this->requestOrderService->getMyRequests(
            $user->id,
            $filters
        );

        return $this->sendResponse(
            $result,
            'تم جلب طلباتي بنجاح'
        );
    }

    /**
     * إلغاء طلب
     * DELETE /api/request-orders/{id}
     */
    public function cancel(int $id, Request $request)
    {
        $result = $this->requestOrderService->cancelRequest(
            $id,
            $request->user()->id
        );

        return $this->sendResponse(
            new RequestOrderResource($result),
            'تم إلغاء الطلب بنجاح'
        );
    }

    /**
     * موافقة مدير المستشفى
     * PATCH /api/request-orders/{id}/manager-approve
     */
    public function approveByManager($id, Request $request)
{
    $requestOrder = $this->requestOrderService->approveByManager(
        $id,
        $request->user()->id  // ← مرر الـ userId
    );

    return $this->sendResponse(
        new RequestOrderResource($requestOrder),
        'تمت الموافقة على الطلب بنجاح'
    );
}

    /**
     * رفض مدير المستشفى
     * PATCH /api/request-orders/{id}/manager-reject
     */
    public function rejectByManager(
    RejectRequestOrderRequest $request,
    $id
) {
    $requestOrder = $this->requestOrderService->rejectByManager(
        $id,
        $request->validated()['rejection_reason'],
        $request->user()->id  // ← مرر الـ userId
    );

    return $this->sendResponse(
        new RequestOrderResource($requestOrder),
        'تم رفض الطلب بنجاح'
    );
}

    /**
     * موافقة المستودع
     * PATCH /api/request-orders/{id}/warehouse-approve
     */
    public function approveByWarehouse(
    ApproveRequestOrderByWarehouseRequest $request,
    int $id
) {
    $result = $this->requestOrderService->approveByWarehouse(
        $id,
        $request->validated()['items'],
        $request->user()->id  // ← مرر الـ userId
    );

    return $this->sendResponse(
        new RequestOrderResource($result),
        'تمت الموافقة على الطلب وتجهيزه للتسليم'
    );
}

    /**
     * رفض المستودع
     * PATCH /api/request-orders/{id}/warehouse-reject
     */
    public function rejectByWarehouse(
    RejectRequestOrderRequest $request,
    int $id
) {
    $result = $this->requestOrderService->rejectByWarehouse(
        $id,
        $request->validated()['rejection_reason'],
        $request->user()->id  // ← مرر الـ userId
    );

    return $this->sendResponse(
        new RequestOrderResource($result),
        'تم رفض الطلب بنجاح'
    );
}

    /**
     * طلبات المدير العادية ()
     * GET /api/request-orders/manager/pending/normal
     */
    public function getPendingNormalRequests()
    {
        $requests = $this->requestOrderService->getPendingNormalRequests();

        return $this->sendResponse(
            RequestOrderResource::collection($requests),
            'تم جلب الطلبات العادية بنجاح'
        );
    }

    /**
     * طلبات المدير المستعجلة ()
     * GET /api/request-orders/manager/pending/urgent
     */
    public function getPendingUrgentRequests()
    {
        $requests = $this->requestOrderService->getPendingUrgentRequests();

        return $this->sendResponse(
            RequestOrderResource::collection($requests),
            'تم جلب الطلبات المستعجلة بنجاح'
        );
    }

    /**
     * طلبات المستودع العادية ()
     * GET /api/request-orders/warehouse/pending/normal
     */
    public function warehousePendingNormal()
    {
        $requests = $this->requestOrderService->getWarehousePendingNormalRequests();

        return $this->sendResponse(
            RequestOrderResource::collection($requests),
            'تم جلب الطلبات العادية بنجاح'
        );
    }

    /**
     * طلبات المستودع المستعجلة ()
     * GET /api/request-orders/warehouse/pending/urgent
     */
    public function warehousePendingUrgent()
    {
        $requests = $this->requestOrderService->getWarehousePendingUrgentRequests();

        return $this->sendResponse(
            RequestOrderResource::collection($requests),
            'تم جلب الطلبات المستعجلة بنجاح'
        );
    }

    /**
     * تفاصيل طلب ()
     * GET /api/request-orders/{id}
     */
    public function show($id)
    {
        $requestOrder = $this->requestOrderService->getRequestOrderById($id);

        return $this->sendResponse(
            new RequestOrderResource($requestOrder),
            'تم جلب تفاصيل الطلب بنجاح'
        );
    }

    /**
     * تأكيد الاستلام ()
     * PATCH /api/request-orders/{id}/confirm-delivery
     */
    public function confirmDelivery($id)
    {
        $requestOrder = $this->requestOrderService->confirmDelivery($id);

        return $this->sendResponse(
            new RequestOrderResource($requestOrder),
            'تم تأكيد استلام الطلب بنجاح'
        );
    }

    /**
     * رفض الاستلام ()
     * PATCH /api/request-orders/{id}/reject-delivery
     */
    public function rejectDelivery(
        RejectRequestOrderRequest $request,
        int $id
    ) {
        $result = $this->requestOrderService->rejectDelivery(
            $id,
            $request->validated()['rejection_reason']
        );

        return $this->sendResponse(
            new RequestOrderResource($result),
            'تم رفض الاستلام بنجاح'
        );
    }

    /**
     * طلبات قيد التنفيذ
     * GET /api/request-orders/in-progress
     */
    public function getInProgressRequests()
    {
        $requests = $this->requestOrderService->getInProgressRequests();

        return $this->sendResponse(
            RequestOrderResource::collection($requests),
            'تم جلب الطلبات قيد التنفيذ بنجاح'
        );
    }

    /**
     * جميع طلبات الأقسام
     ** GET /api/request-orders/all
     */
    public function getAllDepartmentRequests()
    {
        $requests = $this->requestOrderService->getAllDepartmentRequests();

        return $this->sendResponse(
            RequestOrderResource::collection($requests),
            'تم جلب جميع الطلبات بنجاح'
        );
    }
}
