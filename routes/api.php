<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\WarehouseController;
use App\Http\Controllers\DepartmentHeadController;
use App\Http\Controllers\PurchaseRequestController;

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
        [RequestOrderController::class, 'pendingNormal']
    );

    Route::get(
        '/request-orders/pending/urgent',
        [RequestOrderController::class, 'pendingUrgent']
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

//تفاصيل طلب شراء 



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






    Route::post('/verify-otp', [AuthController::class, 'verifyOtp']);
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

});






//قسم عام 


Route::post('/login', [AuthController::class, 'login']);
//Route::post('/login-web', [AuthController::class, 'loginWeb']);




    Route::post('/verify-otp', [AuthController::class, 'verifyOtp']);
    Route::post('/set-password', [AuthController::class, 'setPassword']);


Route::get('/test-resend-key', function () {
    return env('RESEND_API_KEY');
});





Route::middleware(['auth:sanctum', 'role:department_head'])
    ->prefix('department-head')
    ->group(function () {
        Route::post('/requests', [DepartmentHeadController::class, 'store']);
        Route::get('/requests', [DepartmentHeadController::class, 'index']);
        Route::delete('/requests/{id}', [DepartmentHeadController::class, 'cancel']);
    });

