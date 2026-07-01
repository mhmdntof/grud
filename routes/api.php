<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\WarehouseController;
use App\Http\Controllers\DepartmentHeadController;
use App\Http\Controllers\PurchaseRequestController;
use App\Http\Controllers\DepartmentController;
use App\Http\Controllers\PrescriptionController;

use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;
use App\Http\Controllers\RequestOrderController;



Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');



//قسم الادمن


Route::middleware([
    'auth:sanctum',
    'role:admin'
])->group(function () {

Route::post('/create-hospital-manager', [AuthController::class, 'createHospitalManager']);

});


        Route::get(
    '/purchase-requests/{id}',
    [PurchaseRequestController::class, 'show']
)->middleware('auth:sanctum');


//قسم مدير المشفى


Route::middleware([
    'auth:sanctum',
    'role:hospital_manager'
])->group(function () {

Route::post('/create-employee', [AuthController::class, 'createEmployee']);
 Route::post('/resend-otp', [AuthController::class, 'resendOtp']);
Route::post('/add-products', [ProductController::class, 'store']);
Route::patch('/requests/{id}/manager-approval',[RequestOrderController::class,'managerApproval']
);
  Route::get(
        '/request-orders/pending/normal',
        [RequestOrderController::class, 'getPendingNormalRequests']
    );

    Route::get(
        '/request-orders/pending/urgent',
        [RequestOrderController::class, 'getPendingUrgentRequests']
    );

  Route::patch(
            '/purchase-requests/{id}/approve',
            [PurchaseRequestController::class, 'approveManager']
        );

        Route::patch(
            '/purchase-requests/{id}/reject',
            [PurchaseRequestController::class, 'rejectManager']
        );


            Route::get(
        '/purchase-requests/manager/pending/urgent',
        [PurchaseRequestController::class, 'pendingManagerUrgent']
    );

    Route::get(
        '/purchase-requests/manager/pending/normal',
        [PurchaseRequestController::class, 'pendingManagerNormal']
    );


    //موافقة لمدير على طلب قسم
Route::patch(
    '/request-orders/{id}/approve',
    [RequestOrderController::class, 'approveByManager']
);
//رفض المدير لطلب قسم
Route::patch(
    '/request-orders/{id}/reject',
    [RequestOrderController::class, 'rejectByManager']
);

//جلب الطلبات قيد التنفيذ

Route::get(
    '/request-orders/in-progress',
    [RequestOrderController::class, 'getInProgressRequests']
);



 });


//قسم مدير المستودع




Route::middleware([
    'auth:sanctum',
    'role:warehouse_manager'
])->group(function () {

    Route::post('/add-products', [ProductController::class, 'store']);
    Route::post('/add-batch',[ProductController::class,'addBatch']);
 Route::post(  '/requests/{id}/warehouse-approval',[RequestOrderController::class,'warehouseApproval']
    );


 Route::get(
        '/warehouse-requests/pending/normal',
        [RequestOrderController::class,
        'warehousePendingNormal']
    );

    Route::get(
        '/warehouse-requests/pending/urgent',
        [RequestOrderController::class,
        'warehousePendingUrgent']
    );


  Route::post(
            '/purchase-requests',
            [PurchaseRequestController::class, 'store']
        );

//الموافقة على طلب القسم

Route::patch(
    '/request-orders/{id}/warehouse-approve',
    [RequestOrderController::class,
     'approveByWarehouse']
);

//رفض طلب القسم

Route::patch(
    '/request-orders/{id}/warehouse-reject',
    [RequestOrderController::class,
     'rejectByWarehouse']
);



});



//رئيس لجنة الشراء

Route::middleware(['auth:sanctum', 'role:purchase_committee_head'])
    ->group(function () {

        Route::patch(
            '/purchase-requests/{id}/committee/approve',
            [PurchaseRequestController::class, 'approveCommittee']
        );

        Route::patch(
            '/purchase-requests/{id}/committee/reject',
            [PurchaseRequestController::class, 'rejectCommittee']
        );



        //تفاصيل طلب شراء


    });









//قسم عام


  Route::get(
        '/request-orders/{id}',
        [RequestOrderController::class, 'show']
    );



    Route::post('/send', [AuthController::class, 'sendOtp']);
    Route::post('/set-password', [AuthController::class, 'setPassword']);







//قسم رئيس القسم


Route::middleware([
    'auth:sanctum',
    'role:department_head'
])->group(function () {

    Route::post('/request-items', [
        RequestOrderController::class,
        'store'
    ]);



      Route::get(
        '/purchase-requests/committee/pending/urgent',
        [PurchaseRequestController::class, 'pendingCommitteeUrgent']
    );

    Route::get(
        '/purchase-requests/committee/pending/normal',
        [PurchaseRequestController::class, 'pendingCommitteeNormal']
    );

    //استلام المواد من المستودع

    Route::patch(
    '/request-orders/{id}/confirm-delivery',
    [RequestOrderController::class,
     'confirmDelivery']
);

Route::patch(
    '/request-orders/{id}/reject-delivery',
    [RequestOrderController::class,
     'rejectDelivery']
);


});






//قسم عام


Route::post('/login', [AuthController::class, 'login']);
//Route::post('/login-web', [AuthController::class, 'loginWeb']);


//استلام دفعة

Route::post('/add-batch',[ProductController::class,'receive']);

    Route::post('/verify-otp', [AuthController::class, 'verifyOtp']);
    Route::post('/set-password', [AuthController::class, 'setPassword']);


Route::get('/test-resend-key', function () {
    return env('RESEND_API_KEY');
});

//جلب مواد المستودع الرئيسي

Route::get(
    '/warehouse/products/{type}',
    [ProductController::class, 'getWarehouseProducts']
);


//جلب مواد القسم

Route::get(
    '/departments/{departmentName}/products/{type}',
    [DepartmentController::class, 'getDepartmentProducts']
);



//جلب مواد المستودع

Route::get(
    '/get/warehouse/products',
    [ProductController::class, 'getAllWarehouseProducts']
);

//جلب جميع طلبات الاقسام
Route::get(
    '/get/department-requests',
    [RequestOrderController::class, 'getAllDepartmentRequests']
);


//اضافة مورد
Route::post(
    '/add-supplirs',
    [ProductController::class, 'addSupplirs']

);

// ربط المورد بالمادة


Route::post('/products/attach-supplier', [ProductController::class, 'attachSupplier']);

//جلب الموردين مع المواد

Route::get('/get-all-Suppliers', [ProductController::class, 'getAllSuppliersWithProducts']);

//جلب مواد المستودع مع تاريخ اخر دفعة

Route::get(
    '/get/warehouse/products/with/date',
    [ProductController::class, 'getAllWarehouseProductsWith']
);

//طلبات الشراء العادية

Route::get('/get/requests/purchase/normal',[WarehouseController::class,'getNormalWarehouseRequests']);
Route::get('/get/requests/purchase/urgent',[WarehouseController::class,'getUrgentWarehouseRequests']);


















Route::middleware(['auth:sanctum', 'role:department_head'])
    ->prefix('department-head')
    ->group(function () {
        Route::post('/requests', [DepartmentHeadController::class, 'store']);
        Route::get('/requests', [DepartmentHeadController::class, 'index']);
        Route::delete('/requests/{id}', [DepartmentHeadController::class, 'cancel']);
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
        $message->to('ايميلك@example.com')
                ->subject('Mail Test');
    });

    return response()->json([
        'message' => 'Mail sent successfully'
    ]);
});


Route::middleware(['auth:sanctum', 'is.pharmacy'])
    ->prefix('prescriptions')
    ->group(function () {
        // عرض كل الوصفات (من جميع الأقسام)
        Route::get('/all', [PrescriptionController::class, 'index']);

        // إحصائيات كل الوصفات
        Route::get('/statistics', [PrescriptionController::class, 'statistics']);

        // تحديث حالة الوصفة (موافقة/رفض/معالجة)
        Route::patch('/{id}/status', [PrescriptionController::class, 'updateStatus'])
            ->where('id', '[0-9]+'); // ← يقبل الأرقام فقط!
    });

// 👨‍⚕️ رؤساء الأقسام
Route::middleware(['auth:sanctum', 'role:department_head'])
    ->prefix('prescriptions')
    ->group(function () {
        // رفع وصفة جديدة
        Route::post('/', [PrescriptionController::class, 'store']);

        // عرض وصافاتي فقط
        Route::get('/my-prescriptions', [PrescriptionController::class, 'myPrescriptions']);

        // إحصائيات وصافاتي
        Route::get('/my-statistics', [PrescriptionController::class, 'myStatistics']);

        // تفاصيل وصفة - ⚠️ مهم جداً: where('id', '[0-9]+')
        Route::get('/{id}', [PrescriptionController::class, 'show'])
            ->where('id', '[0-9]+');

        // حذف وصفة
        Route::delete('/{id}', [PrescriptionController::class, 'destroy'])
            ->where('id', '[0-9]+');
    });





// ========================================
// نظام الطلبات (Request Orders) - Resource-centric
// ========================================

// طلبات عامة (لجميع الأدوار المسجلة)
    Route::middleware(['auth:sanctum'])
    ->prefix('request-orders')
    ->group(function () {
        // تفاصيل طلب
        Route::get('/{id}', [RequestOrderController::class, 'show'])
            ->where('id', '[0-9]+');
    });

// رئيس القسم - إنشاء وعرض طلباته
Route::middleware(['auth:sanctum', 'role:department_head'])
    ->prefix('request-orders')
    ->group(function () {
        // إنشاء طلب جديد
        Route::post('/', [RequestOrderController::class, 'store']);

        // عرض طلباتي
        Route::get('/my-requests', [RequestOrderController::class, 'myRequests']);

        // إلغاء طلب
        Route::delete('/{id}', [RequestOrderController::class, 'cancel'])
            ->where('id', '[0-9]+');

        // تأكيد الاستلام
        Route::patch('/{id}/confirm-delivery', [RequestOrderController::class, 'confirmDelivery'])
            ->where('id', '[0-9]+');

        // رفض الاستلام
        Route::patch('/{id}/reject-delivery', [RequestOrderController::class, 'rejectDelivery'])
            ->where('id', '[0-9]+');
    });

// مدير المستشفى - الموافقة/الرفض
Route::middleware(['auth:sanctum', 'role:hospital_manager'])
    ->prefix('request-orders')
    ->group(function () {
        // طلبات عادية
        Route::get('/manager/pending/normal', [RequestOrderController::class, 'getPendingNormalRequests']);

        // طلبات مستعجلة
        Route::get('/manager/pending/urgent', [RequestOrderController::class, 'getPendingUrgentRequests']);

        // موافقة
        Route::patch('/{id}/manager-approve', [RequestOrderController::class, 'approveByManager'])
            ->where('id', '[0-9]+');

        // رفض
        Route::patch('/{id}/manager-reject', [RequestOrderController::class, 'rejectByManager'])
            ->where('id', '[0-9]+');

        // طلبات قيد التنفيذ
        Route::get('/in-progress', [RequestOrderController::class, 'getInProgressRequests']);
    });

// مدير المستودع - الموافقة/الرفض
Route::middleware(['auth:sanctum', 'role:warehouse_manager'])
    ->prefix('request-orders')
    ->group(function () {
        // طلبات عادية
        Route::get('/warehouse/pending/normal', [RequestOrderController::class, 'warehousePendingNormal']);

        // طلبات مستعجلة
        Route::get('/warehouse/pending/urgent', [RequestOrderController::class, 'warehousePendingUrgent']);

        // موافقة
        Route::patch('/{id}/warehouse-approve', [RequestOrderController::class, 'approveByWarehouse'])
            ->where('id', '[0-9]+');

        // رفض
        Route::patch('/{id}/warehouse-reject', [RequestOrderController::class, 'rejectByWarehouse'])
            ->where('id', '[0-9]+');
    });

// جميع الطلبات (للأدمن)
Route::middleware(['auth:sanctum', 'role:admin'])
    ->prefix('request-orders')
    ->group(function () {
        Route::get('/all', [RequestOrderController::class, 'getAllDepartmentRequests']);
    });
