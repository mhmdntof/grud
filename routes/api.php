<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;

use App\Http\Controllers\AuthController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\WarehouseController;
use App\Http\Controllers\DepartmentHeadController;
use App\Http\Controllers\SupplierController;
use App\Http\Controllers\PurchaseRequestController;
use App\Http\Controllers\DepartmentController;
use App\Http\Controllers\RequestOrderController;
use App\Http\Controllers\InventoryController;

// ========================================
// User Info (Sanctum)
// ========================================
Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

// ========================================
// Public Routes (Authentication)
// ========================================
Route::post('/login', [AuthController::class, 'login']);
Route::post('/send', [AuthController::class, 'sendOtp']);
Route::post('/verify-otp', [AuthController::class, 'verifyOtp']);
Route::post('/set-password', [AuthController::class, 'setPassword']);

// ========================================
// Admin Routes
// ========================================
Route::middleware(['auth:sanctum', 'role:admin'])->group(function () {
    Route::post('/create-hospital-manager', [AuthController::class, 'createHospitalManager']);
});

// ========================================
// Hospital Manager Routes
// ========================================
Route::middleware(['auth:sanctum', 'role:hospital_manager'])->group(function () {
    Route::post('/create-employee', [AuthController::class, 'createEmployee']);
    Route::post('/resend-otp', [AuthController::class, 'resendOtp']);
    Route::post('/add-products', [ProductController::class, 'store']);

    // Request Orders - Manager Approval (  محمد)
    Route::patch('/requests/{id}/manager-approval', [RequestOrderController::class, 'managerApproval']);
    Route::patch('/request-orders/{id}/approve', [RequestOrderController::class, 'approveByManager']);
    Route::patch('/request-orders/{id}/reject', [RequestOrderController::class, 'rejectByManager']);

    // Request Orders - Pending (محمد)
    Route::get('/request-orders/pending/normal', [RequestOrderController::class, 'getPendingNormalRequests']);
    Route::get('/request-orders/pending/urgent', [RequestOrderController::class, 'getPendingUrgentRequests']);
    Route::get('/request-orders/in-progress', [RequestOrderController::class, 'getInProgressRequests']);

    // Purchase Requests ( محمد)
    Route::patch('/purchase-requests/{id}/approve', [PurchaseRequestController::class, 'approveManager']);
    Route::patch('/purchase-requests/{id}/reject', [PurchaseRequestController::class, 'rejectManager']);
    Route::get('/purchase-requests/manager/pending/urgent', [PurchaseRequestController::class, 'pendingManagerUrgent']);
    Route::get('/purchase-requests/manager/pending/normal', [PurchaseRequestController::class, 'pendingManagerNormal']);
});

// ========================================
// Warehouse Manager Routes محمد
// ========================================
Route::middleware(['auth:sanctum', 'role:warehouse_manager'])->group(function () {
    // Products & Batches (  محمد)
    Route::post('/add-products', [ProductController::class, 'store']);
    Route::post('/add-batch', [ProductController::class, 'addBatch']);

    // Request Orders - Warehouse Approval (  محمد)
    Route::post('/requests/{id}/warehouse-approval', [RequestOrderController::class, 'warehouseApproval']);
    Route::patch('/request-orders/{id}/warehouse-approve', [RequestOrderController::class, 'approveByWarehouse']);
    Route::patch('/request-orders/{id}/warehouse-reject', [RequestOrderController::class, 'rejectByWarehouse']);

    // Request Orders - Pending (  محمد)
    Route::get('/warehouse-requests/pending/normal', [RequestOrderController::class, 'warehousePendingNormal']);
    Route::get('/warehouse-requests/pending/urgent', [RequestOrderController::class, 'warehousePendingUrgent']);

    // Purchase Requests (  محمد)
    Route::post('/purchase-requests', [PurchaseRequestController::class, 'store']);

    //   جعفر  (Warehouse System)
    Route::prefix('warehouse')->group(function () {
        Route::post('/stock-in', [WarehouseController::class, 'stockIn']);
        Route::post('/stock-out', [WarehouseController::class, 'stockOut']);
        Route::post('/damage', [WarehouseController::class, 'damage']);
        Route::get('/alerts', [WarehouseController::class, 'alerts']);
        Route::get('/products', [WarehouseController::class, 'index']);
        Route::get('/requests', [WarehouseController::class, 'requestOrders']);

        //  جعفر  (approve, reject, prepare, ready, deliver)
        Route::post('/requests/{id}/approve', [WarehouseController::class, 'approveRequestOrder']);
        Route::post('/requests/{id}/reject', [WarehouseController::class, 'rejectRequestOrder']);
        Route::post('/requests/{id}/prepare', [WarehouseController::class, 'prepareRequestOrder']);
        Route::post('/requests/{id}/ready', [WarehouseController::class, 'readyRequestOrder']);
        Route::post('/requests/{id}/deliver', [WarehouseController::class, 'deliverRequestOrder']);
        //  Suppliers CRUD ( جعفر)
        Route::get('/suppliers', [SupplierController::class, 'index']);
        Route::get('/suppliers/{id}', [SupplierController::class, 'show']);
        Route::post('/suppliers', [SupplierController::class, 'store']);
        Route::put('/suppliers/{id}', [SupplierController::class, 'update']);
        Route::delete('/suppliers/{id}', [SupplierController::class, 'destroy']);
        Route::post('/suppliers/{id}/restore', [SupplierController::class, 'restore']);

        //  Movements Archive ( جعفر)
        Route::get('/movements', [WarehouseController::class, 'movements']);
    });

        //  نظام الجرد (WS-9)
    Route::prefix('inventory')->group(function () {
        Route::get('/', [InventoryController::class, 'index']);
        Route::post('/', [InventoryController::class, 'store']);
        Route::get('/{id}', [InventoryController::class, 'show']);
        Route::post('/{id}/record', [InventoryController::class, 'recordQuantities']);
        Route::post('/{id}/complete', [InventoryController::class, 'complete']);
        Route::post('/{id}/approve', [InventoryController::class, 'approve']);
    });
});

// ========================================
// Purchase Committee Head Routes
// ========================================
Route::middleware(['auth:sanctum', 'role:purchase_committee_head'])->group(function () {
    Route::patch('/purchase-requests/{id}/committee/approve', [PurchaseRequestController::class, 'approveCommittee']);
    Route::patch('/purchase-requests/{id}/committee/reject', [PurchaseRequestController::class, 'rejectCommittee']);
});

// ========================================
// Department Head Routes
// ========================================
Route::middleware(['auth:sanctum', 'role:department_head'])->group(function () {
    // Request Orders (  محمد)
    Route::post('/request-items', [RequestOrderController::class, 'store']);

    // Purchase Requests (  محمد)
    Route::get('/purchase-requests/committee/pending/urgent', [PurchaseRequestController::class, 'pendingCommitteeUrgent']);
    Route::get('/purchase-requests/committee/pending/normal', [PurchaseRequestController::class, 'pendingCommitteeNormal']);

    // Delivery Confirmation (  محمد)
    Route::patch('/request-orders/{id}/confirm-delivery', [RequestOrderController::class, 'confirmDelivery']);
    Route::patch('/request-orders/{id}/reject-delivery', [RequestOrderController::class, 'rejectDelivery']);

    //   جعفر  (Department Head System)
    Route::prefix('department-head')->group(function () {
        Route::post('/requests', [DepartmentHeadController::class, 'store']);
        Route::get('/requests', [DepartmentHeadController::class, 'index']);
        Route::delete('/requests/{id}', [DepartmentHeadController::class, 'cancel']);
        Route::put('/requests/{id}', [DepartmentHeadController::class, 'update']);
        Route::get('/available-products', [DepartmentHeadController::class, 'availableProducts']);
        Route::post('/requests/{id}/confirm-receipt', [DepartmentHeadController::class, 'confirmReceipt']); //تأكيد الكمية المستلمة
        Route::post('/return-request', [DepartmentHeadController::class, 'returnRequest']);
        });
});

// ========================================
// Public Routes (General -   محمد)
// ========================================
Route::get('/purchase-requests/{id}', [PurchaseRequestController::class, 'show'])->middleware('auth:sanctum');
Route::get('/request-orders/{id}', [RequestOrderController::class, 'show']);

Route::get('/warehouse/products/{type}', [ProductController::class, 'getWarehouseProducts']);
Route::get('/departments/{departmentName}/products/{type}', [DepartmentController::class, 'getDepartmentProducts']);
Route::get('/get/warehouse/products', [ProductController::class, 'getAllWarehouseProducts']);
Route::get('/get/department-requests', [RequestOrderController::class, 'getAllDepartmentRequests']);

Route::post('/add-supplirs', [ProductController::class, 'addSupplirs']);
Route::post('/products/attach-supplier', [ProductController::class, 'attachSupplier']);
Route::get('/get-all-Suppliers', [ProductController::class, 'getAllSuppliersWithProducts']);
Route::get('/get/warehouse/products/with/date', [ProductController::class, 'getAllWarehouseProductsWith']);

// ========================================
// Test Routes (Development Only -   محمد)
// ========================================
Route::get('/test-resend-key', function () {
    return env('RESEND_API_KEY');
});

Route::get('/mail-config', function () {
    return [
        'mailer' => config('mail.default'),
        'host' => config('mail.mailers.smtp.host'),
        'port' => config('mail.mailers.smtp.port'),
        'encryption' => config('mail.mailers.smtp.encryption'),
        'username' => config('mail.mailers.smtp.username'),
        'from' => config('mail.from.address'),
    ];
});

Route::get('/test-mail', function () {
    Mail::raw('Laravel mail test', function ($message) {
        $message->to('test@example.com')->subject('Mail Test');
    });

    return response()->json(['message' => 'Mail sent successfully']);
});
