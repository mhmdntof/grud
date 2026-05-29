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



use Illuminate\Support\Facades\Auth;

Route::get('/test-session', function () {

    session(['test' => 'working']);

    return [
        'session_id' => session()->getId(),
        'session' => session('test'),
        'user' => Auth::user(),
        'cookies' => request()->cookies->all(),
    ];

})->middleware('auth:sanctum');







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

Route::post('/login', [AuthController::class, 'login']);
//Route::post('/login-web', [AuthController::class, 'loginWeb']);


Route::get('/test', function () {
    return 'IT WORKS';
});


    Route::post('/verify-otp', [AuthController::class, 'verifyOtp']);
    Route::post('/set-password', [AuthController::class, 'setPassword']);


  Route::get('/mail-test', function () {

    try {

        Mail::raw('HELLO FROM BREVO', function ($message) {

            $message->to('mntwf38@gmail.com')
                ->subject('Brevo Test');

        });

        return response()->json([
            'message' => 'MAIL SENT'
        ]);

    } catch (\Throwable $e) {

        return response()->json([
            'error' => $e->getMessage()
        ], 500);
    }
});


Route::get('/test-render', function () {

    Log::info('RENDER TEST OK');

    return response()->json([
        'message' => 'Render is working fine'
    ]);
});


Route::get('/test-smtp-connection', function () {

    $host = 'smtp.gmail.com';
    $port = 587;

    $connection = @fsockopen($host, $port, $errno, $errstr, 10);

    if (!$connection) {
        return [
            'status' => 'FAILED',
            'error' => "$errstr ($errno)"
        ];
    }

    fclose($connection);

    return [
        'status' => 'SUCCESS - Port open'
    ];
});

Route::get('/test-mail-local', function () {

    try {

        Mail::raw('Test', function ($m) {
            $m->to('your_email@gmail.com')
              ->subject('Test');
        });

        return 'MAIL SENT';

    } catch (\Throwable $e) {

        return $e->getMessage();
    }
});

Route::get('/test-email', function () {

    $response = Http::withHeaders([
        'Authorization' => 'Bearer ' . env('RESEND_API_KEY'),
    ])->post('https://api.resend.com/emails', [
        'from' => 'Hospital <onboarding@resend.dev>',
        'to' => 'your_email@gmail.com',
        'subject' => 'Test Email',
        'html' => '<h1>OTP TEST</h1>',
    ]);

    return $response->json();
});

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
