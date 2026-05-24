<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\AuthController;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;

Route::get('/', function () {
    return view('welcome');
});

Route::post('/login', [AuthController::class, 'loginWeb']);
Route::middleware('auth:sanctum')->get('/me', [AuthController::class, 'me']);
Route::get('/debug-session', function (Request $request) {



//مدير المستودع 

Route::middleware([
    'auth:sanctum',
    'role:warehouse_manager'
])->group(function () {

    Route::post('/add-products', [ProductController::class, 'store']);

});




    return response()->json([
        'authenticated' => Auth::check(),
        'user' => Auth::user(),
        'session_id' => session()->getId(),
        'all_cookies' => $request->cookies->all(),
        'xsrf_header' => $request->header('X-XSRF-TOKEN'),
    ]);

})->middleware('auth:sanctum');