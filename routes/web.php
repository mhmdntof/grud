<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ProductController;

use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\DepartmentController;
use App\Http\Controllers\AuthWebController;
Route::get('/', function () {
    return view('welcome');
});

Route::post('/login', [AuthWebController::class, 'loginWeb']);

Route::middleware('auth:sanctum')->group(function () {

    Route::get('/me', [AuthWebController::class, 'me']);

    Route::post('/logout', [AuthWebController::class, 'logoutWeb']);

});




//مدير المستودع 

Route::middleware([
    'auth:sanctum',
    'role:warehouse_manager'
])->group(function () {

    Route::post('/add-products', [ProductController::class, 'store']);

});
 
 