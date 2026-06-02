<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\WarehouseController;
use App\Http\Controllers\DepartmentHeadController;

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
 feature/department-head-system
// Warehouse Manager Routes
Route::middleware(['auth:sanctum', 'role:warehouse_manager'])
    ->prefix('warehouse')
    ->group(function () {
        Route::post('/stock-in', [WarehouseController::class, 'stockIn']);
        Route::post('/stock-out', [WarehouseController::class, 'stockOut']);
        Route::post('/damage', [WarehouseController::class, 'damage']);
        Route::get('/alerts', [WarehouseController::class, 'alerts']);
        Route::get('/products', [WarehouseController::class, 'index']);
        Route::get('/requests', [WarehouseController::class, 'requests']);
    });





//قسم عام





});
 feature/department-head-system


    Route::post('/verify-otp', [AuthController::class, 'verifyOtp']);
    Route::post('/set-password', [AuthController::class, 'setPassword']);


  Route::get('/mail-test', function () {




//قسم رئيس القسم 


Route::middleware([
    'auth:sanctum',
    'role:department_head'
])->group(function () {

    Route::post('/request-items', [
        RequestOrderController::class,
        'store'
    ]);

});






//قسم عام 


Route::post('/login', [AuthController::class, 'login']);
//Route::post('/login-web', [AuthController::class, 'loginWeb']);


Route::get('/test', function () {
    return 'IT WORKS';
});
    

    Route::post('/verify-otp', [AuthController::class, 'verifyOtp']);
    Route::post('/set-password', [AuthController::class, 'setPassword']);

 feature/department-head-system
Route::get('/test-resend-key', function () {
    return env('RESEND_API_KEY');
});




// ─── Department Head ───
Route::middleware(['auth:sanctum', 'role:department_head'])
    ->prefix('department-head')
    ->group(function () {
        Route::post('/requests', [DepartmentHeadController::class, 'store']);
        Route::get('/requests', [DepartmentHeadController::class, 'index']);
        Route::delete('/requests/{id}', [DepartmentHeadController::class, 'cancel']);
    });

