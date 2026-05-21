<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\ProductController;

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

 });


//قسم مدير المستودع











//قسم عام 

Route::post('/login', [AuthController::class, 'login']);
Route::post('/login-web', [AuthController::class, 'loginWeb']);

Route::middleware('auth:sanctum')
    ->get('/me', [AuthController::class, 'me']);

Route::get('/test', function () {
    return 'IT WORKS';
});
    

    Route::post('/verify-otp', [AuthController::class, 'verifyOtp']);
    Route::post('/set-password', [AuthController::class, 'setPassword']);

   